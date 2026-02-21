<?php

namespace App\Filament\Resources\Inventories\Schemas;

use App\Enums\InventoryItemType;
use App\Enums\InventoryStatus;
use App\Models\Inventory;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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

                        Select::make('item_type')
                            ->label('Item type')
                            ->options(InventoryItemType::options())
                            ->default(InventoryItemType::Consumable->value)
                            ->required()
                            ->live(),

                        Select::make('status')
                            ->options(InventoryStatus::options())
                            ->default(InventoryStatus::Available->value)
                            ->required(),

                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->helperText('For assets usually keep quantity = 1.'),

                        Select::make('unit')
                            ->options([
                                'piece' => 'Piece',
                                'kg' => 'Kg',
                                'liter' => 'Liter',
                            ])
                            ->required(fn (Get $get) => $get('item_type') !== InventoryItemType::Asset->value)
                            ->visible(fn (Get $get) => $get('item_type') !== InventoryItemType::Asset->value),

                        TextInput::make('cost_price')
                            ->label('Cost price')
                            ->numeric()
                            ->minValue(0)
                            ->required(fn (Get $get) => $get('item_type') !== InventoryItemType::Asset->value)
                            ->visible(fn (Get $get) => $get('item_type') !== InventoryItemType::Asset->value),

                        TextInput::make('sell_price')
                            ->label('Sell price')
                            ->numeric()
                            ->minValue(0)
                            ->visible(fn (Get $get) => $get('item_type') === InventoryItemType::Retail->value),
                    ]),

                Section::make('Asset Details')
                    ->columns(2)
                    ->visible(fn (Get $get) => $get('item_type') === InventoryItemType::Asset->value)
                    ->schema([
                        TextInput::make('asset_tag')
                            ->maxLength(255)
                            ->rules([
                                function (Get $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        if ($get('item_type') !== InventoryItemType::Asset->value || blank($value)) {
                                            return;
                                        }

                                        $query = Inventory::query()
                                            ->where('item_type', InventoryItemType::Asset->value)
                                            ->where('asset_tag', $value);

                                        if ($recordId = self::currentRecordId()) {
                                            $query->whereKeyNot($recordId);
                                        }

                                        if ($query->exists()) {
                                            $fail('Asset tag must be unique for assets.');
                                        }
                                    };
                                },
                            ]),

                        TextInput::make('serial_number')
                            ->maxLength(255)
                            ->rules([
                                function (Get $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        if ($get('item_type') !== InventoryItemType::Asset->value || blank($value)) {
                                            return;
                                        }

                                        $query = Inventory::query()
                                            ->where('item_type', InventoryItemType::Asset->value)
                                            ->where('serial_number', $value);

                                        if ($recordId = self::currentRecordId()) {
                                            $query->whereKeyNot($recordId);
                                        }

                                        if ($query->exists()) {
                                            $fail('Serial number must be unique for assets.');
                                        }
                                    };
                                },
                            ]),

                        Select::make('condition')
                            ->options([
                                'new' => 'New',
                                'good' => 'Good',
                                'repair' => 'Needs repair',
                                'damaged' => 'Damaged',
                            ])
                            ->required(fn (Get $get) => $get('item_type') === InventoryItemType::Asset->value),

                        Select::make('hall_id')
                            ->label('Hall')
                            ->relationship('hall', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => $get('item_type') === InventoryItemType::Asset->value)
                            ->required(fn (Get $get) => $get('item_type') === InventoryItemType::Asset->value),

                        DatePicker::make('purchase_date')
                            ->default(now())
                            ->required(fn (Get $get) => $get('item_type') === InventoryItemType::Asset->value),

                        TextInput::make('purchase_price')
                            ->numeric()
                            ->minValue(0)
                            ->required(fn (Get $get) => $get('item_type') === InventoryItemType::Asset->value)
                            ->live(),

                        DatePicker::make('warranty_until'),
                    ]),
            ]);
    }

    private static function currentRecordId(): ?int
    {
        $record = request()->route('record');

        if (is_numeric($record)) {
            return (int) $record;
        }

        if (is_object($record) && isset($record->id)) {
            return (int) $record->id;
        }

        return null;
    }
}

