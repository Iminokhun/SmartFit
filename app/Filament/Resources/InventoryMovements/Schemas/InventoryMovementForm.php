<?php

namespace App\Filament\Resources\InventoryMovements\Schemas;

use App\Enums\InventoryItemType;
use App\Models\Inventory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class InventoryMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('inventory_id')
                    ->label('Inventory Item')
                    ->relationship(
                        name: 'inventory',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->where('item_type', '!=', InventoryItemType::Asset->value),
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) =>
                        "{$record->name} - {$record->item_type?->label()} - Qty: {$record->quantity}" . ($record->unit ? " {$record->unit}" : '')
                    )
                    ->preload()
                    ->searchable()
                    ->required()
                    ->reactive(),

                Select::make('type')
                    ->options([
                        'in' => 'In (Arrival)',
                        'out' => 'Out (Write-off)',
                    ])
                    ->required(),

                TextInput::make('quantity')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->rules([
                        function (Get $get) {
                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                $inventory = Inventory::find($get('inventory_id'));

                                if (! $inventory) {
                                    return;
                                }

                                if ($inventory->isAsset()) {
                                    $fail('Stock movement is not allowed for asset items. Use asset status/lifecycle updates instead.');

                                    return;
                                }

                                if ($get('type') === 'out' && $value > $inventory->quantity) {
                                    $fail("Not enough items in stock. Available: {$inventory->quantity}");
                                }
                            };
                        },
                    ]),

                Textarea::make('description')
                    ->label('Description / Reason')
                    ->rows(3)
                    ->placeholder('Example: Issued to project A, Returned from repair...')
                    ->reactive()
                    ->required(),
            ]);
    }
}


