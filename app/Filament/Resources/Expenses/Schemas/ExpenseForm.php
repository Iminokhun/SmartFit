<?php

namespace App\Filament\Resources\Expenses\Schemas;

use App\Models\ExpenseCategory;
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
                Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
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
                    ->required(function (Get $get) {
                        $categoryId = $get('category_id');
                        if (! $categoryId) {
                            return false;
                        }

                        return (bool) ExpenseCategory::query()
                            ->whereKey($categoryId)
                            ->value('requires_staff');
                    })
                    ->visible(function (Get $get) {
                        $categoryId = $get('category_id');
                        if (! $categoryId) {
                            return false;
                        }

                        return (bool) ExpenseCategory::query()
                            ->whereKey($categoryId)
                            ->value('requires_staff');
                    }),

                Textarea::make('description')
                    ->columnSpanFull(),

            ]);
    }
}
