<?php

namespace App\Filament\Resources\Staff\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StaffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Staff Information')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([

                        TextInput::make('full_name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('specialization')
                            ->required(),

                        TextInput::make('experience_years')
                            ->numeric()
                            ->minValue(0)
                            ->nullable(),

                        Select::make('role_id')
                            ->label('Role')
                            ->relationship('role', 'name')
                            ->preload()
                            ->searchable()
                            ->required()
                            ->createOptionForm([
                                    TextInput::make('name')
                                        ->maxLength(255)
                                        ->required()
                            ]
                            ),


                        TextInput::make('phone')
                            ->tel()
                            ->required(),

                        TextInput::make('email')
                            ->email()
                            ->nullable(),

                        FileUpload::make('photo')
                            ->image()
                            ->directory('staff-photos'),

                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'vacation' => 'Vacation',
                            ])
                            ->default('active')
                            ->required(),

                        Select::make('salary_type')
                            ->options([
                                'fixed' => 'Fixed',
                                'percent' => 'Percent',
                                'per_session' => 'Per session',
                            ])
                            ->default('fixed')
                            ->required(),

                        TextInput::make('salary')
                            ->numeric()
                            ->minValue(10)
                            ->required(),

                        Repeater::make('shifts')
                            ->relationship('shifts')
                            ->schema([
                                Select::make('days_of_week')
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
                                    ->required(),

                                TimePicker::make('start_time')
                                    ->seconds(false)
                                    ->required(),

                                TimePicker::make('end_time')
                                    ->seconds(false)
                                    ->required(),
                            ])
                            ->columns(3)
                            ->columnSpanFull()
                            ->grid(1),
                    ]),
            ]);
    }
}
