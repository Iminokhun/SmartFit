<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Select::make('role_id')
                            ->label('Role')
                            ->relationship('role', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->rule(Password::min(8))
                            ->maxLength(255),
                    ]),
            ]);
    }
}

