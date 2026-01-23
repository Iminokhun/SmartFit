<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('full_name')
                ->required(),

                DatePicker::make('birth_date')
                ->displayFormat('d/m/Y')
                ->placeholder('dd/mm/yyyy')
                ->native(false)
                ->required(),

                TextInput::make('phone')
                    ->label('Phone number')
                    ->required()
                    ->unique()
                    ->rule(['required', 'digits:9', 'numeric'])
                    ->helperText('Enter a valid phone number (no spaces or symbols)'),

                TextInput::make('email')
                    ->label('Email address')
                    ->unique(),

                Radio::make('gender')
                ->options([
                    'male' => 'Male',
                    'female' => 'Female',
                ])
                ->required(),

                FileUpload::make('photo')
                    ->image()
                    ->directory('customers')
                    ->nullable(),

                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'blocked' => 'Blocked',
                        'deleted' => 'Deleted',
                    ])
                    ->default('active')
                    ->required(),
            ]);
    }
}
