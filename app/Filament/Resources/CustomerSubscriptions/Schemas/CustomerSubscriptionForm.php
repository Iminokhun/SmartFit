<?php

namespace App\Filament\Resources\CustomerSubscriptions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerSubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
//
                    Select::make('customer_id')
                        ->relationship('customer', 'full_name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('subscription_id')
                        ->relationship('subscription', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    DatePicker::make('start_date')
                        ->required()
                        ->default(now()),

                    DatePicker::make('end_date')
                        ->required(),

                    TextInput::make('remaining_visits')
                        ->numeric()
                        ->nullable(),

                    Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'expired' => 'Expired',
                            'frozen' => 'Frozen',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('active')
                        ->required(),
                ]);
    }
}
