<?php

namespace App\Filament\Resources\Payments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.full_name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customerSubscription.subscription.name')
                    ->label('Subscription')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->money('UZS') //
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) =>
                    $record->amount >= $record->customerSubscription?->subscription?->price
                        ? 'success'
                        : 'danger'
                    ),

                TextColumn::make('remaining_amount')
                    ->label('Remaining')
                    ->money('UZS')
                    ->badge()
                    ->state(function ($record) {
                        $price = $record->customerSubscription?->subscription?->price ?? 0;
                        return max(0, $price - $record->amount);
                    })
                    ->color(fn ($record) =>
                    $record->amount >= $record->customerSubscription?->subscription?->price
                        ? 'success'
                        : 'warning'
                    ),

                TextColumn::make('method')
                    ->searchable()
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),


            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => auth()->user()?->role === 'admin' || auth()->user()?->role === 'manager'),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()?->role === 'admin' || auth()->user()?->role === 'manager'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->role === 'admin' || auth()->user()?->role === 'manager'),
                ]),
            ]);
    }
}
