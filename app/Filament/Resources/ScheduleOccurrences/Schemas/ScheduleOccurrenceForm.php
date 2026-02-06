<?php

namespace App\Filament\Resources\ScheduleOccurrences\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class ScheduleOccurrenceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('schedule_id')
                    ->label('Schedule')
                    ->relationship('schedule', 'id')
                    ->searchable()
                    ->preload()
                    ->required(),

                DatePicker::make('date')
                    ->required(),

                TimePicker::make('start_time')
                    ->label('Start time')
                    ->nullable(),

                TimePicker::make('end_time')
                    ->label('End time')
                    ->nullable(),

                TextInput::make('max_participants')
                    ->label('Max participants')
                    ->numeric()
                    ->nullable(),

                Select::make('status')
                    ->options([
                        'planned' => 'Planned',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('planned')
                    ->required(),
            ]);
    }
}

