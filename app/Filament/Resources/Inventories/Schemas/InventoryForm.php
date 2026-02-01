<?php

namespace App\Filament\Resources\Inventories\Schemas;

use App\Enums\InventoryStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InventoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Inventory Info')
                    ->columns(2)
                    ->schema([

                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(0)
                            ->required(),

                        Select::make('status')
                            ->options(InventoryStatus::options())
                            ->default(InventoryStatus::Available->value)
                            ->required(),
                    ]),
            ]);
    }
}
