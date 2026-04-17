<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Support\FilamentActions;
use App\Filament\Support\FilamentColumns;
use App\Filament\Support\FilamentFilters;
use App\Models\Customer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn ($record) => CustomerResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('full_name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('phone')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('birth_date')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('gender')
                    ->formatStateUsing(fn ($state) => Str::ucfirst($state))
                    ->badge()
                    ->sortable()
                    ->searchable(),

                FilamentColumns::statusBadge('status', [
                    'active'   => 'success',
                    'inactive' => 'danger',
                    'blocked'  => 'warning',
                    'deleted'  => 'gray',
                ], 'Status')->searchable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->multiple()
                    ->options([
                        'active'   => 'Active',
                        'inactive' => 'Inactive',
                        'blocked'  => 'Blocked',
                        'deleted'  => 'Deleted',
                    ]),

                SelectFilter::make('gender')
                    ->multiple()
                    ->options([
                        'male'   => 'Male',
                        'female' => 'Female',
                    ]),

                FilamentFilters::dateRange('created_at', 'Created range'),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                FilamentActions::deleteWithPolicy(),
            ])
            ->toolbarActions([
                FilamentActions::bulkDeleteWithPolicy(Customer::class),
            ]);
    }
}
