<?php

namespace App\Filament\Resources\Inventories\Tables;

use App\Enums\InventoryItemType;
use App\Enums\InventoryStatus;
use App\Filament\Resources\Expenses\ExpenseResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoriesTable
{
    private const LOW_STOCK_THRESHOLD = 10;

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),

                TextColumn::make('item_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (InventoryItemType|string|null $state) => $state instanceof InventoryItemType ? $state->label() : ucfirst((string) $state))
                    ->color(fn (InventoryItemType|string|null $state) => match ($state instanceof InventoryItemType ? $state->value : $state) {
                        'asset' => 'gray',
                        'consumable' => 'info',
                        'retail' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('quantity')
                    ->label('Qty')
                    ->formatStateUsing(fn ($state, $record) => $record->unit ? "{$state} {$record->unit}" : (string) $state)
                    ->badge()
                    ->color(function ($record) {
                        if ($record->item_type?->value === InventoryItemType::Asset->value) {
                            return 'gray';
                        }

                        return (int) $record->quantity <= self::LOW_STOCK_THRESHOLD ? 'warning' : 'success';
                    })
                    ->sortable(),

                TextColumn::make('stock_alert')
                    ->label('Stock alert')
                    ->badge()
                    ->state(function ($record) {
                        if ($record->item_type?->value === InventoryItemType::Asset->value) {
                            return '-';
                        }

                        return (int) $record->quantity <= self::LOW_STOCK_THRESHOLD ? 'Low stock' : 'OK';
                    })
                    ->color(function ($record) {
                        if ($record->item_type?->value === InventoryItemType::Asset->value) {
                            return 'gray';
                        }

                        return (int) $record->quantity <= self::LOW_STOCK_THRESHOLD ? 'warning' : 'success';
                    }),

                TextColumn::make('hall.name')
                    ->label('Location')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('condition')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('expense_id')
                    ->label('Expense #')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('expense.amount')
                    ->label('Expense amount')
                    ->money('UZS')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('expense.expenses_date')
                    ->label('Expense date')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (InventoryStatus|string|null $state) => $state instanceof InventoryStatus ? $state->label() : ucfirst((string) $state))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('item_type')
                    ->label('Type')
                    ->options(InventoryItemType::options()),

                SelectFilter::make('status')
                    ->options(InventoryStatus::options()),

                SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('has_expense')
                    ->label('Has expense')
                    ->options([
                        'with' => 'With expense',
                        'without' => 'Without expense',
                    ])
                    ->query(function (Builder $query, $state): Builder {
                        if ($state === 'with') {
                            return $query->whereNotNull('expense_id');
                        }

                        if ($state === 'without') {
                            return $query->whereNull('expense_id');
                        }

                        return $query;
                    }),

                Filter::make('low_stock')
                    ->label('Low stock')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereIn('item_type', [InventoryItemType::Consumable->value, InventoryItemType::Retail->value])
                        ->where('quantity', '<=', self::LOW_STOCK_THRESHOLD)),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('openExpense')
                    ->label('Open expense')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => $record->expense_id ? ExpenseResource::getUrl('edit', ['record' => $record->expense_id]) : null)
                    ->openUrlInNewTab()
                    ->visible(fn ($record): bool => ! empty($record->expense_id)),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
