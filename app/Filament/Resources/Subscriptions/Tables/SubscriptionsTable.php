<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Models\Subscription;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(
                fn ($record) => SubscriptionResource::getUrl('view', ['record' => $record])
            )
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('activity.name')
                    ->label('Activity')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('duration_days')
                    ->label('Duration')
                    ->suffix(' days')
                    ->sortable(),

                TextColumn::make('visits_limit')
                    ->label('Visit limit')
                    ->sortable(),

                TextColumn::make('price')
                    ->money('UZS')
                    ->sortable(),

                TextColumn::make('discount')
                    ->suffix('%')

            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
