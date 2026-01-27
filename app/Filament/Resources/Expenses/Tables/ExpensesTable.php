<?php

namespace App\Filament\Resources\Expenses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                TextColumn::make('amount')
                    ->money('UZS')
                    ->sortable(),

                TextColumn::make('expenses_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('staff.full_name')
                    ->label('Staff')
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
