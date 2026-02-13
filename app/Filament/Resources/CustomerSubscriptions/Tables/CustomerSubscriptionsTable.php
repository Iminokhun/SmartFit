<?php

namespace App\Filament\Resources\CustomerSubscriptions\Tables;

use App\Models\Activity;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'frozen' => 'warning',
                        'cancelled' => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst((string) $state))
                    ->label('Status'),

                TextColumn::make('paid_amount')
                    ->label('Paid')
                    ->money('UZS')
                    ->sortable(),

                TextColumn::make('debt')
                    ->label('Debt')
                    ->money('UZS')
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'unpaid' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst((string) $state))

            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->multiple()
                    ->options([
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'frozen' => 'Frozen',
                        'cancelled' => 'Cancelled',
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

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')
                            ->label('Start from'),
                        DatePicker::make('until')
                            ->label('End until'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['from']) && empty($data['until'])) {
                            return;
                        }
                        if (!empty($data['from'])) {
                            $query->whereDate('start_date', '>=', $data['from']);
                        }
                        if (!empty($data['until'])) {
                            $query->whereDate('end_date', '<=', $data['until']);
                        }
                    }),
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
