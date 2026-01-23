<?php

namespace App\Filament\Resources\Staff\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                            ->required(),
                    ]),
            ]);
    }
}
