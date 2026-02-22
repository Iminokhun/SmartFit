<?php

namespace App\Filament\Resources\Subscriptions\Pages;

use App\Filament\Resources\InventoryMovements\InventoryMovementResource;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewSubscription extends ViewRecord
{
    protected static string $resource = SubscriptionResource::class;

    public function getTitle(): string
    {
        return $this->record->name ?? 'Subscription';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->url(SubscriptionResource::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscription Info')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Name')
                            ->weight('bold')
                            ->size('lg'),

                        TextEntry::make('activity.name')
                            ->label('Activity')
                            ->weight('bold')
                            ->size('lg'),

                        TextEntry::make('description')
                            ->label('Description')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('duration_days')
                            ->label('Duration')
                            ->suffix(' days')
                            ->weight('bold'),

                        TextEntry::make('visits_limit')
                            ->label('Visit limit')
                            ->state(fn ($record) => $record->visits_limit ?? 'Unlimited')
                            ->weight('bold'),

                        TextEntry::make('limit_summary')
                            ->label('Capacity')
                            ->state(fn ($record) => $record->capacityLabel())
                            ->badge()
                            ->color(fn ($record) => $record->capacityColor())
                            ->columnSpanFull(),
                    ]),

                Section::make('Pricing')
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        TextEntry::make('price')
                            ->label('Price')
                            ->money('UZS')
                            ->weight('bold'),

                        TextEntry::make('discount')
                            ->label('Discount')
                            ->suffix('%')
                            ->weight('bold'),

                        TextEntry::make('final_price')
                            ->label('Final price')
                            ->money('UZS')
                            ->state(function ($record) {
                                $price = $record->price ?? 0;
                                $discount = $record->discount ?? 0;
                                return max(0, $price - ($price * $discount / 100));
                            })
                            ->weight('bold'),

                        TextEntry::make('total_revenue')
                            ->label('Total revenue')
                            ->money('UZS')
                            ->state(function ($record) {
                                return Payment::query()
                                    ->whereIn('status', ['paid', 'partial'])
                                    ->whereHas('customerSubscription', function ($query) use ($record) {
                                        $query->where('subscription_id', $record->id);
                                    })
                                    ->sum('amount');
                            })
                            ->weight('bold'),
                    ]),

                Section::make('Customers')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('customers')
                            ->label('Purchased by')
                            ->schema([
                                TextEntry::make('customer.full_name')
                                    ->label('Customer')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color('info')
                                    ->url(fn ($record) => \App\Filament\Resources\Customers\CustomerResource::getUrl('view', ['record' => $record->customer_id]))
                                    ->openUrlInNewTab(),

                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        'active' => 'success',
                                        'expired' => 'danger',
                                        'frozen' => 'warning',
                                        'cancelled' => 'gray',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => ucfirst((string) $state)),

                                TextEntry::make('start_date')
                                    ->label('Start')
                                    ->date(),

                                TextEntry::make('end_date')
                                    ->label('End')
                                    ->date(),

                                TextEntry::make('remaining_visits')
                                    ->label('Visits left')
                                    ->state(fn ($record) => $record->remaining_visits ?? 'Unlimited'),

                                TextEntry::make('paid_amount')
                                    ->label('Paid')
                                    ->money('UZS'),

                                TextEntry::make('debt')
                                    ->label('Debt')
                                    ->money('UZS'),

                                TextEntry::make('payment_status')
                                    ->label('Payment')
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        'paid' => 'success',
                                        'partial' => 'warning',
                                        'unpaid' => 'gray',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => ucfirst((string) $state)),
                            ])
                            ->columns(8)
                            ->columnSpanFull(),
                    ])

            ]);
    }
}
