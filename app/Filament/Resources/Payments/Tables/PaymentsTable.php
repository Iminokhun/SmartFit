<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
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

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'pending' => 'gray',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst((string) $state)),

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
                        'paid' => 'Paid',
                        'partial' => 'Partial',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                    ]),

                Filter::make('created_at')
                    ->label('Date range')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): void {
                        if (! empty($data['from'])) {
                            $query->whereDate('created_at', '>=', $data['from']);
                        }

                        if (! empty($data['until'])) {
                            $query->whereDate('created_at', '<=', $data['until']);
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn ($record) => auth()->user()?->can('update', $record)),
                DeleteAction::make()
                    ->visible(fn ($record) => auth()->user()?->can('delete', $record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('deleteAny', Payment::class)),
                ]),
            ]);
    }
}

