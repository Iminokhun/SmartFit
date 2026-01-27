<?php

namespace App\Filament\Resources\Expenses\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category')
                ->label('Category')
                ->options([
                    'rent' => 'Rent',
                    'salary' => 'Salary',
                    'equipment' => 'Equipment',
                    'marketing' => 'Marketing',
                    'utilities' => 'Utilities',
                    'other' => 'Other',
                ])
                ->required()
                ->reactive(),

                TextInput::make('amount')
                ->label('Amount')
                ->numeric()
                ->minValue(0)
                ->required(),

                DatePicker::make('expenses_date')
                    ->default(now())
                    ->required(),

                Select::make('staff_id')
                    ->relationship('staff', 'full_name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->required(fn (Get $get) => $get('category') === 'salary')
                    ->visible(fn (Get $get) => $get('category') === 'salary'),

                Textarea::make('description')
                    ->columnSpanFull(),

            ]);
    }
}
