<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Filament\Support\FilamentActions;
use App\Filament\Support\FilamentColumns;
use App\Models\Activity;
use App\Models\Hall;
use App\Models\Staff;
use App\Models\Subscription;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
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
                    ->formatStateUsing(fn ($state) => $state ?? 'Unlimited')
                    ->sortable(),

                TextColumn::make('limit_summary')
                    ->label('Capacity')
                    ->state(fn ($record) => $record->capacityLabel())
                    ->badge()
                    ->color(fn ($record) => $record->capacityColor()),

                FilamentColumns::money('price'),

                TextColumn::make('final_price')
                    ->label('Final price')
                    ->money('UZS')
                    ->state(function ($record) {
                        $price = $record->price ?? 0;
                        $discount = $record->discount ?? 0;
                        return max(0, $price - ($price * $discount / 100));
                    })
                    ->sortable(),

                TextColumn::make('discount')
                    ->suffix('%')

            ])
            ->filters([
                SelectFilter::make('activity_id')
                    ->label('Activity')
                    ->multiple()
                    ->options(fn () => Activity::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload(),

                SelectFilter::make('trainer_id')
                    ->label('Trainer')
                    ->multiple()
                    ->options(fn () => Staff::query()->orderBy('full_name')->pluck('full_name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->placeholder('All trainers'),

                SelectFilter::make('hall_id')
                    ->label('Hall')
                    ->multiple()
                    ->options(fn () => Hall::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->preload(),

                TernaryFilter::make('has_discount')
                    ->label('Discount')
                    ->placeholder('Any')
                    ->trueLabel('With discount')
                    ->falseLabel('No discount')
                    ->queries(
                        true:  fn (Builder $q) => $q->where('discount', '>', 0),
                        false: fn (Builder $q) => $q->where('discount', '<=', 0),
                    ),

                TernaryFilter::make('visits_limit')
                    ->label('Visit limit')
                    ->placeholder('Any')
                    ->trueLabel('Unlimited')
                    ->falseLabel('Limited')
                    ->queries(
                        true:  fn (Builder $q) => $q->whereNull('visits_limit'),
                        false: fn (Builder $q) => $q->whereNotNull('visits_limit'),
                    ),

                Filter::make('price_range')
                    ->label('Price range')
                    ->form([
                        TextInput::make('price_from')
                            ->label('From (UZS)')
                            ->numeric()
                            ->placeholder('0'),
                        TextInput::make('price_to')
                            ->label('To (UZS)')
                            ->numeric()
                            ->placeholder('any'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['price_from'])) {
                            $query->where('price', '>=', $data['price_from']);
                        }
                        if (!empty($data['price_to'])) {
                            $query->where('price', '<=', $data['price_to']);
                        }
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (!empty($data['price_from'])) {
                            $indicators[] = 'Price from ' . number_format($data['price_from']) . ' UZS';
                        }
                        if (!empty($data['price_to'])) {
                            $indicators[] = 'Price to ' . number_format($data['price_to']) . ' UZS';
                        }
                        return $indicators;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                FilamentActions::deleteWithPolicy(),
            ])
            ->toolbarActions([
                FilamentActions::bulkDeleteWithPolicy(Subscription::class),
            ]);
    }
}
