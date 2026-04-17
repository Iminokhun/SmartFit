<?php

namespace App\Filament\Resources\Schedules\Schemas;

use App\Rules\HallAvailabilityRule;
use App\Rules\TrainerShiftAvailabilityRule;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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

                Select::make('trainer_id')
                    ->label('Trainer')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->relationship(
                        'staff',
                        'full_name',
                        fn ($query) => $query->whereHas(
                            'role',
                            fn ($q) => $q->where('name', 'Trainer')
                        )
                    )
                    ->rules([
                        fn (Get $get) => new TrainerShiftAvailabilityRule(
                            trainerId: $get('trainer_id'),
                            startTime: $get('start_time'),
                            endTime: $get('end_time'),
                            daysOfWeek: $get('days_of_week') ?? [],
                            recordId: $get('id')
                            ?? (is_object(request()->route('record'))
                                ? request()->route('record')->id
                                : request()->route('record')),
                        ),
                    ]),

                Select::make('hall_id')
                    ->label('Hall')
                    ->relationship('hall', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->rules([
                        fn (Get $get) => new HallAvailabilityRule(
                            startTime: $get('start_time'),
                            endTime: $get('end_time'),
                            daysOfWeek: $get('days_of_week') ?? [],
                            recordId: $get('id')
                            ?? (is_object(request()->route('record'))
                                ? request()->route('record')->id
                                : request()->route('record')),
                        ),
                    ])
                    ->createOptionForm([
                            TextInput::make('name')
                            ->required(),
                            Textarea::make('description'),
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
                    ->reactive()
                    ->afterStateUpdated(fn (Get $get, Set $set) =>
                        $set('hall_id', $get('hall_id'))
                    )
                    ->afterStateUpdated(fn (Get $get, \Filament\Schemas\Components\Utilities\Set $set) =>
                    $set('trainer_id', $get('trainer_id'))
                    ),

                TimePicker::make('start_time')
                    ->required()
                    ->seconds(false)
                    ->reactive()
                    ->afterStateUpdated(fn (Get $get, Set $set) =>
                        $set('hall_id', $get('hall_id'))
                    )
                    ->afterStateUpdated(fn (Get $get, Set $set) =>
                    $set('trainer_id', $get('trainer_id'))
                    ),

                TimePicker::make('end_time')
                    ->required()
                    ->seconds(false)
                    ->reactive()
                    ->rules([
                        fn (Get $get) => function (string $attribute, $value, $fail) use ($get) {
                            if ($get('start_time') && $value <= $get('start_time')) {
                                $fail('End time must be after start time.');
                            }
                        },
                    ])
                    ->afterStateUpdated(fn (Get $get, Set $set) =>
                        $set('hall_id', $get('hall_id'))
                    )
                    ->afterStateUpdated(fn (Get $get, Set $set) =>
                    $set('trainer_id', $get('trainer_id'))
                    ),

                TextInput::make('max_participants')
                    ->numeric()
                    ->minValue(1)
                ->label('Max participants')
            ]);
    }
}
