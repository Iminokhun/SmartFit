<?php

namespace App\Filament\Resources\CustomerSubscriptions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CustomerSubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table

            ->columns([
                TextColumn::make('customer.full_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subscription.name')
                    ->label('Subscription')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subscription.name')
                    ->label('Active')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('remaining_visits')
                    ->label('Visits left')
                    ->sortable(),

                TextColumn::make('status')
                    ->color([
                        'success' => 'active',
                        'grey' => 'frozen',
                        'danger' => 'expired',
                        'warning' => 'cancelled',
                    ])
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'frozen' => 'warning',
                        'cancelled' => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst((string) $state))

            ])
            ->filters([
                //
            ])
            ->recordActions([
                DeleteAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
