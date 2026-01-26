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


                TextInput::make('hall')
                    ->required(),

                Select::make('day_of_week')
                    ->options([
                        'monday' => 'Monday',
                        'tuesday' => 'Tuesday',
                        'wednesday' => 'Wednesday',
                        'thursday' => 'Thursday',
                        'friday' => 'Friday',
                        'saturday' => 'Saturday',
                        'sunday' => 'Sunday',
                    ])
                    ->required(),

                TimePicker::make('start_time')
                    ->required(),

                TimePicker::make('end_time')
                    ->required()
                    ->rules([
                        fn (Get $get) => function (string $attribute, $value, $fail) use ($get) {
                            if ($get('start_time') && $value <= $get('start_time')) {
                                $fail('End time must be after start time.');
                            }
                        },
                ]),

                TextInput::make('max_participants')
                    ->numeric()
                    ->minValue(1)
                ->label('Max participants')
            ]);
    }
}
