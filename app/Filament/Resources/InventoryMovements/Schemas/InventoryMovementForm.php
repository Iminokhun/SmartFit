<?php

namespace App\Filament\Resources\InventoryMovements\Schemas;

use App\Enums\InventoryStatus;
use App\Models\Inventory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

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
                    titleAttribute: 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) =>
                    "{$record->name}  -  {$record->status->label()}  -  Qty: {$record->quantity}"
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

                                if ($get('type') === 'out' && $value > $inventory->quantity) {
                                    $fail("Not enough items in stock. Available: {$inventory->quantity}");
                                }
                            };
                        }
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
