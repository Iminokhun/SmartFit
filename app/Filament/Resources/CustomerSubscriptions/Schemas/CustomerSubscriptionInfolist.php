<?php

namespace App\Filament\Resources\CustomerSubscriptions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerSubscriptionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer')
                    ->schema([
                        TextEntry::make('customer.full_name')
                            ->label('Name'),
                ]),

                Section::make('Subscription')
                    ->schema([
                        TextEntry::make('subscription.name')
                            ->label('Plan'),

                        TextEntry::make('subscription.activity.name')
                            ->label('Activity'),
                    ])
                    ->columns(2),

                Section::make('Usage')
                    ->schema([
                        TextEntry::make('visits_left')
                            ->label('Visits left')
                            ->placeholder('Unlimited'),

                        TextEntry::make('status')
                            ->badge()
                            ->colors([
                                'active' => 'Active',
                                'expired' => 'Expired',
                                'frozen' => 'Frozen',
                                'cancelled' => 'Cancelled',
                            ]),

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
                    ->columns(3),
            ]);
    }
}
