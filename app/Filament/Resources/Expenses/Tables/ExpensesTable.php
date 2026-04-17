<?php

namespace App\Filament\Resources\Expenses\Tables;

use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Support\FilamentActions;
use App\Filament\Support\FilamentColumns;
use App\Filament\Support\FilamentFilters;
use App\Models\Expense;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn ($record) => ExpenseResource::getUrl('view', ['record' => $record]))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->badge(),

                FilamentColumns::money('amount'),

                TextColumn::make('expenses_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('description')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('staff.full_name')
                    ->label('Staff')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('staff_id')
                    ->label('Staff')
                    ->relationship('staff', 'full_name')
                    ->searchable()
                    ->preload(),

                FilamentFilters::dateRange('expenses_date', 'Date range'),
            ])
            ->recordActions([
                FilamentActions::editWithPolicy(),
                FilamentActions::deleteWithPolicy(),
            ])
            ->toolbarActions([
                FilamentActions::bulkDeleteWithPolicy(Expense::class),
            ]);
    }
}
