<?php

namespace App\Filament\Resources\Visits\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class VisitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->relationship('customer', 'full_name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Customer'),

                Select::make('schedule_id')
                    ->relationship('schedule', 'id')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Schedule'),

                DateTimePicker::make('visited_at')
                    ->default(now())
                    ->required()
                    ->label('Visited at'),

                Select::make('status')
                    ->options([
                        'visited' => 'Visited',
                        'missed' => 'Missed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('visited')
                    ->required(),
            ]);
    }
}

