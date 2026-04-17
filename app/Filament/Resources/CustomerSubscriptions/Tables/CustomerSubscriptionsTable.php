<?php

namespace App\Filament\Resources\CustomerSubscriptions\Tables;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Support\FilamentActions;
use App\Filament\Support\FilamentColumns;
use App\Models\Activity;
use App\Models\CustomerSubscription;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerSubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn ($record) => $record->customer_id
                ? CustomerResource::getUrl('view', ['record' => $record->customer_id])
                : null)
            ->columns([
                TextColumn::make('customer.full_name')
                    ->label('Customer')
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

                FilamentColumns::statusBadge('status', [
                    'active'    => 'success',
                    'pending'   => 'info',
                    'expired'   => 'danger',
                    'frozen'    => 'warning',
                    'cancelled' => 'gray',
                ], 'Status'),

                FilamentColumns::money('paid_amount', 'Paid'),
                FilamentColumns::money('debt', 'Debt'),

                FilamentColumns::statusBadge('payment_status', [
                    'paid'    => 'success',
                    'partial' => 'warning',
                    'unpaid'  => 'gray',
                ], 'Payment'),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->multiple()
                    ->options([
                        'active'    => 'Active',
                        'pending'   => 'Pending',
                        'expired'   => 'Expired',
                        'frozen'    => 'Frozen',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('payment_status')
                    ->label('Payment')
                    ->multiple()
                    ->options([
                        'paid'    => 'Paid',
                        'partial' => 'Partial',
                        'unpaid'  => 'Unpaid',
                    ]),

                SelectFilter::make('activity')
                    ->label('Activity')
                    ->multiple()
                    ->options(fn () => Activity::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $state) {
                        $activityIds = $state['values'] ?? [];

                        if (empty($activityIds)) {
                            return;
                        }

                        $query->whereHas('subscription', function (Builder $subQuery) use ($activityIds) {
                            $subQuery->whereIn('activity_id', $activityIds);
                        });
                    }),

                Filter::make('debt_open')
                    ->label('Debt > 0')
                    ->query(fn (Builder $query): Builder => $query->where('debt', '>', 0)),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')->label('Start from'),
                        DatePicker::make('until')->label('End until'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['from']) && empty($data['until'])) {
                            return;
                        }

                        if (! empty($data['from'])) {
                            $query->whereDate('start_date', '>=', $data['from']);
                        }

                        if (! empty($data['until'])) {
                            $query->whereDate('end_date', '<=', $data['until']);
                        }
                    }),
            ])
            ->recordActions([
                FilamentActions::deleteWithPolicy(),
                \Filament\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                FilamentActions::bulkDeleteWithPolicy(CustomerSubscription::class),
            ]);
    }
}
