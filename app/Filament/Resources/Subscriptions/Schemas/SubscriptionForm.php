<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscription Info')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->rows(3),
                    ])
                    ->columns(2),

                Section::make('Rules')
                    ->schema([
                        TextInput::make('duration_days')
                            ->numeric()
                            ->required()
                            ->suffix('Days'),

                        TextInput::make('visits_limit')
                            ->numeric()
                            ->required()
                    ])
                    ->columns(2),

                Section::make('Pricing')
                    ->schema([
                        TextInput::make('price')
                        ->required()
                        ->numeric()
                        ->prefix('UZS'),

                        TextInput::make('discount')
                        ->numeric()
                        ->suffix('%')
                        ->default(0)
                        ->minValue(0)
                        ->maxValue(100),

                        Placeholder::make('final_price')
                            ->label('Final price')
                            ->content(function (Get $get) {
                                $price = (float) ($get('price') ?? 0);
                                $discount = (float) ($get('discount') ?? 0);

                                if ($price <= 0) {
                                    return '-';
                                }

                                $final = $price - ($price * $discount / 100);
                                return number_format($final, 2) . ' UZS';
                            })
                    ]),
                Section::make('Activity')
                    ->schema([
                        Select::make('activity_id')
                        ->relationship('activity', 'name')
                        ->preload()
                        ->required()
                    ])

            ]);
    }
}
