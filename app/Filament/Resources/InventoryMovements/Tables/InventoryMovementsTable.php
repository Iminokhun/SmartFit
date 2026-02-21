<?php

namespace App\Filament\Resources\InventoryMovements\Tables;

use App\Filament\Resources\InventoryMovements\InventoryMovementResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InventoryMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn ($record) => InventoryMovementResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('inventory.name')
                    ->label('Inventory')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('inventory.item_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst((string) $state->value)),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state) => $state === 'in' ? 'success' : 'danger')
                    ->searchable(),

                TextColumn::make('quantity')
                    ->label('Qty')
                    ->formatStateUsing(fn ($state, $record) => $record->type === 'out' ? "-{$state}" : "+{$state}"),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime(),

                TextColumn::make('description')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->description)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'in' => 'In',
                        'out' => 'Out',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => self::canManage()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->role === 'admin'),
                ]),
            ]);
    }

    private static function canManage(): bool
    {
        $user = auth()->user();
        $roleName = strtolower((string) ($user?->role?->name ?? ''));

        return in_array($roleName, ['admin', 'manager'], true) || in_array((int) ($user?->role_id ?? 0), [1, 2], true);
    }
}
