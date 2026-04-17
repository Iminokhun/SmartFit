<?php

namespace App\Filament\Resources\Shifts\Schemas;

use App\Models\Shift;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ShiftForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Shift Information')
                    ->columns(2)
                    ->schema([
                        Select::make('staff_id')
                            ->label('Trainer')
                            ->relationship('staff', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('days_of_week')
                            ->label('Days')
                            ->options(self::dayOptions())
                            ->multiple()
                            ->required()
                            ->minItems(1),

                        TimePicker::make('start_time')
                            ->seconds(false)
                            ->required(),

                        TimePicker::make('end_time')
                            ->seconds(false)
                            ->required()
                            ->rule('after:start_time')
                            ->rules([
                                function (Get $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $staffId = $get('staff_id');
                                        $days = (array) ($get('days_of_week') ?? []);
                                        $start = $get('start_time');
                                        $end = $value;

                                        if (! $staffId || empty($days) || ! $start || ! $end) {
                                            return;
                                        }

                                        $query = Shift::query()
                                            ->where('staff_id', $staffId)
                                            ->where('start_time', '<', $end)
                                            ->where('end_time', '>', $start)
                                            ->where(function ($q) use ($days) {
                                                foreach ($days as $day) {
                                                    $q->orWhereJsonContains('days_of_week', $day);
                                                }
                                            });

                                        if ($recordId = self::currentRecordId()) {
                                            $query->whereKeyNot($recordId);
                                        }

                                        if ($query->exists()) {
                                            $fail('Shift overlaps with an existing shift for this trainer.');
                                        }
                                    };
                                },
                            ]),
                    ]),
            ]);
    }

    private static function dayOptions(): array
    {
        return [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
        ];
    }

    private static function currentRecordId(): ?int
    {
        $record = request()->route('record');

        if (is_numeric($record)) {
            return (int) $record;
        }

        if (is_object($record) && isset($record->id)) {
            return (int) $record->id;
        }

        return null;
    }
}

