<?php

namespace App\Filament\Resources\CustomerSubscriptions\Schemas;

use App\Models\Subscription;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use App\Enums\PaymentMethod;
use App\Rules\HalfOrFullPaymentAmountRule;


class CustomerSubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                    Select::make('customer_id')
                        ->relationship('customer', 'full_name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('subscription_id')
                        ->relationship('subscription', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            $subscription = Subscription::find($state);
                            if (!$subscription) {
                                $set('remaining_visits', null);
                                $set('end_date', null);
                                return;
                            }

                            $set('remaining_visits', $subscription->visits_limit);

                            $startDate = $get('start_date');
                            if ($startDate) {
                                $endDate = Carbon::parse($startDate)
                                    ->addDays($subscription->duration_days)
                                    ->toDateString();
                                $set('end_date', $endDate);
                            }
                        }),

                    DatePicker::make('start_date')
                        ->required()
                        ->default(now())
                        ->reactive()
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            $subscriptionId = $get('subscription_id');
                            if (!$subscriptionId || !$state) {
                                return;
                            }

                            $subscription = Subscription::find($subscriptionId);
                            if (!$subscription) {
                                return;
                            }

                            $endDate = Carbon::parse($state)
                                ->addDays($subscription->duration_days)
                                ->toDateString();
                            $set('end_date', $endDate);
                        }),

                    DatePicker::make('end_date')
                        ->required()
                        ->rules(['after_or_equal:start_date'])
                        ->minDate(fn (Get $get) => $get('start_date')),

                    TextInput::make('remaining_visits')
                        ->numeric()
                        ->minValue(0)
                        ->nullable()
                        ->rules([
                            function (Get $get) {
                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $subscriptionId = $get('subscription_id');
                                    if (!$subscriptionId || $value === null) {
                                        return;
                                    }

                                    $subscription = Subscription::find($subscriptionId);
                                    if (!$subscription || $subscription->visits_limit === null) {
                                        return;
                                    }

                                    if ($value > $subscription->visits_limit) {
                                        $fail("Remaining visits cannot exceed {$subscription->visits_limit}.");
                                    }
                                };
                            },
                        ]),

                    Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'pending' => 'Pending',
                            'expired' => 'Expired',
                            'frozen' => 'Frozen',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('active')
                        ->required(),

                    Placeholder::make('payment_price_hint')
                        ->label('Price info')
                        ->content(fn (Get $get) => self::priceHint($get))
                        ->dehydrated(false)
                        ->visible(fn (string $operation) => $operation === 'create'),

                    TextInput::make('payment_amount')
                        ->label('Initial payment')
                        ->numeric()
                        ->required()
                        ->rules([
                            fn (Get $get) => new HalfOrFullPaymentAmountRule((int) ($get('subscription_id') ?? 0)),
                        ]),

                    Select::make('payment_method')
                        ->label('Payment method')
                        ->options(PaymentMethod::options())
                        ->visible(fn (string $operation) => $operation === 'create')
                        ->requiredWith('payment_amount'),

                    Textarea::make('payment_description')
                        ->label('Payment note')
                        ->default('Initial payment from subscription create')
                        ->visible(fn (string $operation) => $operation === 'create'),

            ]);


    }

    private static function priceHint(Get $get): string
    {
        $subscriptionId = (int) ($get('subscription_id') ?? 0);

        if ($subscriptionId <= 0) {
            return 'Select a subscription to see price.';
        }

        $subscription = Subscription::find($subscriptionId);
        if (! $subscription) {
            return 'Subscription not found.';
        }

        $full = round((float) $subscription->finalPrice(), 2);
        $half = round($full / 2, 2);

        return 'Full: ' . number_format($full, 2) . ' UZS | Half: ' . number_format($half, 2) . ' UZS';
    }

}
