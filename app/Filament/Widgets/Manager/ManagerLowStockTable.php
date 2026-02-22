<?php

namespace App\Filament\Widgets\Manager;

use App\Enums\InventoryItemType;
use App\Models\Inventory;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ManagerLowStockTable extends BaseWidget
{
    protected static ?string $heading = 'Low Stock Items';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Inventory::query()
                    ->where('item_type', '!=', InventoryItemType::Asset->value)
                    ->where('quantity', '<=', 10)
                    ->orderBy('quantity')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Item')
                    ->searchable(),

                TextColumn::make('item_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? ucfirst((string) $state))
                    ->badge(),

                TextColumn::make('quantity')
                    ->label('Qty')
                    ->sortable(),

                TextColumn::make('unit')
                    ->label('Unit')
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '-')
                    ->badge(),
            ])
            ->emptyStateHeading('No low stock items')
            ->emptyStateDescription('All consumable and retail items are above threshold.')
            ->paginated([10, 25, 50]);
    }
}

