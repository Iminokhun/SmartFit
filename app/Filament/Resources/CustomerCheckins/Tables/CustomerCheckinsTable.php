<?php

namespace App\Filament\Resources\CustomerCheckins\Tables;

use App\Models\Activity;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerCheckinsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),

                TextColumn::make('customer.full_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customerSubscription.subscription.name')
                    ->label('Subscription')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('customerSubscription.subscription.activity.name')
                    ->label('Activity')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('customerSubscription.subscription.trainer.full_name')
                    ->label('Trainer')
                    ->placeholder('-')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('schedule.activity.name')
                    ->label('Schedule activity')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('schedule.hall.name')
                    ->label('Hall')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('schedule.start_time')
                    ->label('Start')
                    ->time()
                    ->placeholder('-'),

                TextColumn::make('schedule.end_time')
                    ->label('End')
                    ->time()
                    ->placeholder('-'),
                TextColumn::make('checkedBy.name')
                    ->label('Scanned by')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('checked_in_at')
                    ->label('Check-in time')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('checkinToken.id')
                    ->label('Token ID')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Filter::make('today')
                    ->query(fn (Builder $query) => $query->whereDate('checked_in_at', now()->toDateString())),

                SelectFilter::make('schedule_id')
                    ->label('Schedule')
                    ->relationship('schedule', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => sprintf(
                        '%s - %s-%s',
                        $record->activity?->name ?? 'Activity',
                        substr((string) $record->start_time, 0, 5),
                        substr((string) $record->end_time, 0, 5),
                    ))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('activity_id')
                    ->label('Activity')
                    ->options(fn () => Activity::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(function ($query, array $data) {
                        if (! $query) {
                            return $query;
                        }
                        $value = $data['value'] ?? null;
                        if (! $value) {
                            return $query;
                        }
                        return $query->whereHas('schedule', fn ($sub) => $sub->where('activity_id', $value));
                    })
                    ->searchable(),

                SelectFilter::make('checked_in_by_user_id')
                    ->label('Scanned by')
                    ->relationship('checkedBy', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
            ])
            ->toolbarActions([

            ]);
    }
}







