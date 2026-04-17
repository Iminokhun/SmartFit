<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Support\FilamentActions;
use App\Filament\Support\FilamentColumns;
use App\Filament\Support\FilamentFilters;
use App\Models\Payment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn ($record) => PaymentResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('customer.full_name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customerSubscription.subscription.name')
                    ->label('Subscription')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->money('UZS')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => match ((string) $record->status) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('remaining_debt')
                    ->label('Remaining')
                    ->money('UZS')
                    ->badge()
                    ->state(fn ($record) => (float) ($record->customerSubscription?->debt ?? 0))
                    ->color(fn ($record) => ((float) ($record->customerSubscription?->debt ?? 0)) > 0 ? 'warning' : 'success'),

                TextColumn::make('method')
                    ->searchable()
                    ->badge(),

                FilamentColumns::statusBadge('status', [
                    'paid'    => 'success',
                    'partial' => 'warning',
                    'pending' => 'gray',
                    'failed'  => 'danger',
                ]),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'paid'    => 'Paid',
                        'partial' => 'Partial',
                        'pending' => 'Pending',
                        'failed'  => 'Failed',
                    ]),

                FilamentFilters::dateRange('created_at', 'Date range'),
            ])
            ->recordActions([
                FilamentActions::editWithPolicy(),
                FilamentActions::deleteWithPolicy(),
            ])
            ->toolbarActions([
                FilamentActions::bulkDeleteWithPolicy(Payment::class),
            ]);
    }
}
