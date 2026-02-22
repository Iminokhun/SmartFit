<?php

namespace App\Filament\Resources\AssetEvents\Tables;

use App\Enums\AssetEventType;
use App\Enums\InventoryStatus;
use App\Filament\Resources\AssetEvents\AssetEventResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssetEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn ($record) => AssetEventResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('event_date')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('inventory.name')
                    ->label('Asset')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('event_type')
                    ->label('Event')
                    ->badge()
                    ->formatStateUsing(fn (AssetEventType|string|null $state) => $state instanceof AssetEventType ? $state->label() : str_replace('_', ' ', ucfirst((string) $state)))
                    ->sortable(),

                TextColumn::make('fromHall.name')
                    ->label('From'),

                TextColumn::make('toHall.name')
                    ->label('To'),

                TextColumn::make('status_before')
                    ->label('Before status')
                    ->badge()
                    ->formatStateUsing(fn (InventoryStatus|string|null $state) => $state instanceof InventoryStatus ? $state->label() : ucfirst((string) $state))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status_after')
                    ->label('After status')
                    ->badge()
                    ->formatStateUsing(fn (InventoryStatus|string|null $state) => $state instanceof InventoryStatus ? $state->label() : ucfirst((string) $state))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('condition_before')
                    ->label('Before condition')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('condition_after')
                    ->label('After condition')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('note')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->note)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('event_type')
                    ->options(AssetEventType::options()),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => self::canManage()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('deleteAny', \App\Models\AssetEvent::class)),
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
