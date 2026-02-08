<?php

namespace App\Filament\Resources\Schedules\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;

class ScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('activity_id')
                    ->label('Activity')
                    ->relationship('activity', 'name')
                    ->preload()
                    ->searchable()
                    ->required(),

//                Select::make('trainer_id')
//                    ->label('Trainer')
//                    ->preload()
//                    ->relationship('staff','full_name')
//                    ->searchable()
//                    ->required(),
                Select::make('trainer_id')
                    ->label('Trainer')
                    ->relationship(
                        'staff',
                        'full_name',
                        fn ($query) => $query->whereHas(
                            'role',
                            fn ($q) => $q->where('name', 'Trainer')
                        )
                    )
                    ->searchable()
                    ->preload()
                    ->required(),


                Select::make('hall_id')
                    ->label('Hall')
                    ->relationship('hall', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->rules([
                        function (Get $get) {
                            return function (string $attribute, $value, $fail) use ($get) {
                                if (!$value) {
                                    return;
                                }

                                $startTime = $get('start_time');
                                $endTime = $get('end_time');
                                $daysOfWeek = $get('days_of_week') ?? [];

                                if (!$startTime || !$endTime || empty($daysOfWeek)) {
                                    return;
                                }

                                // Получаем текущий ID записи (если редактируем)
                                // В Filament v3 можно получить через $get('id') или через Livewire
                                $recordId = $get('id') ?? null;
                                
                                // Альтернативный способ через request (если $get('id') не работает)
                                if (!$recordId) {
                                    $recordId = request()->route('record') ?? null;
                                }

                                // Проверяем пересечения с другими расписаниями
                                $conflictingSchedules = \App\Models\Schedule::query()
                                    ->where('hall_id', $value)
                                    ->when($recordId, fn ($q) => $q->where('id', '!=', $recordId))
                                    ->get()
                                    ->filter(function ($schedule) use ($daysOfWeek, $startTime, $endTime) {
                                        // Проверяем пересечение дней недели
                                        $scheduleDays = $schedule->days_of_week ?? [];
                                        $commonDays = array_intersect($daysOfWeek, $scheduleDays);

                                        if (empty($commonDays)) {
                                            return false; // Нет общих дней - конфликта нет
                                        }

                                        // Проверяем пересечение временных интервалов
                                        $scheduleStart = \Carbon\Carbon::parse($schedule->start_time)->format('H:i:s');
                                        $scheduleEnd = \Carbon\Carbon::parse($schedule->end_time)->format('H:i:s');
                                        $newStart = \Carbon\Carbon::parse($startTime)->format('H:i:s');
                                        $newEnd = \Carbon\Carbon::parse($endTime)->format('H:i:s');

                                        // Интервалы пересекаются, если: (start1 < end2) && (start2 < end1)
                                        $timeOverlaps = ($newStart < $scheduleEnd) && ($scheduleStart < $newEnd);

                                        return $timeOverlaps;
                                    });

                                if ($conflictingSchedules->isNotEmpty()) {
                                    $conflict = $conflictingSchedules->first();
                                    $conflictDays = implode(', ', array_map('ucfirst', array_intersect($daysOfWeek, $conflict->days_of_week ?? [])));
                                    $conflictTime = $conflict->start_time . ' - ' . $conflict->end_time;
                                    $conflictActivity = $conflict->activity->name ?? 'Unknown';

                                    $fail("This hall is already booked on {$conflictDays} at {$conflictTime} for '{$conflictActivity}'. Please choose a different time or hall.");
                                }
                            };
                        },
                    ])
                    ->createOptionForm([
                        \Filament\Forms\Components\TextInput::make('name')
                            ->required(),
                        \Filament\Forms\Components\Textarea::make('description'),
                    ]),

                Select::make('days_of_week')
                    ->label('Days of week')
                    ->options([
                        'monday' => 'Monday',
                        'tuesday' => 'Tuesday',
                        'wednesday' => 'Wednesday',
                        'thursday' => 'Thursday',
                        'friday' => 'Friday',
                        'saturday' => 'Saturday',
                    ])
                    ->multiple()
                    ->minItems(1)
                    ->maxItems(6)
                    ->required()
                    ->afterStateUpdated(function (Get $get, $set, $state) {
                        // Триггерим валидацию зала при изменении дней недели
                        $get('hall_id');
                    }),

                TimePicker::make('start_time')
                    ->required()
                    ->afterStateUpdated(function (Get $get, $set, $state) {
                        // Триггерим валидацию зала при изменении времени
                        $get('hall_id');
                    }),

                TimePicker::make('end_time')
                    ->required()
                    ->rules([
                        fn (Get $get) => function (string $attribute, $value, $fail) use ($get) {
                            if ($get('start_time') && $value <= $get('start_time')) {
                                $fail('End time must be after start time.');
                            }
                        },
                    ])
                    ->afterStateUpdated(function (Get $get, $set, $state) {
                        // Триггерим валидацию зала при изменении времени
                        $get('hall_id');
                    }),

                TextInput::make('max_participants')
                    ->numeric()
                    ->minValue(1)
                ->label('Max participants')
            ]);
    }
}
