<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Models\CustomerSubscription;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->relationship('customer', 'full_name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive(),

                Select::make('customer_subscription_id')
                    ->label('Subscription')
                    ->relationship(
                        'customerSubscription',
                        'id',
                        modifyQueryUsing: fn ($query,  $get) =>
                        $query->where('customer_id', $get('customer_id'))
                            ->with('subscription')
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) =>
                    $record->subscription
                        ? "{$record->subscription->name} | Ends: {$record->end_date}"
                        : "Subscription #{$record->id}"
                    )
                    ->preload()
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $sub = CustomerSubscription::with('subscription')
                            ->find($state);

                        if ($sub && $sub->subscription) {
                            $set('amount', $sub->subscription->finalPrice());
                            $set('status', 'paid');
                        }
                    }),

                Placeholder::make('subscription_price')
                    ->label('Final Price')
                    ->content(function ( $get) {
                        $sub = CustomerSubscription::with('subscription')
                            ->find($get('customer_subscription_id'));

                        return $sub?->subscription
                            ? number_format($sub->subscription->finalPrice(), 2)
                            : '-';
                    })
                    ->reactive(),

                TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->rules([
                        function ($get) {
                            return function (string $attribute, $value, \Closure $fail) use ($get) {

                                $sub = CustomerSubscription::with('subscription')
                                    ->find($get('customer_subscription_id'));

                                if (! $sub || ! $sub->subscription) {
                                    return;
                                }

                                $price = $sub->subscription->finalPrice();
                                $half = round($price / 2, 2);
                                $amount = round((float) $value, 2);

                                if ($amount !== round($price, 2) && $amount !== $half) {
                                    $fail("Amount must be either 50% ({$half}) or full price ({$price}).");
                                }
                            };
                        },
                    ])
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        $sub = CustomerSubscription::with('subscription')
                            ->find($get('customer_subscription_id'));

                        if (! $sub || ! $sub->subscription || $state === null) {
                            return;
                        }

                        $price = $sub->subscription->finalPrice();
                        $half = round($price / 2, 2);
                        $amount = round((float) $state, 2);

                        if ($amount === round($price, 2)) {
                            $set('status', 'paid');
                        } elseif ($amount === $half) {
                            $set('status', 'partial');
                        }
                    })
                    ->helperText(function ($get) {
                        $sub = CustomerSubscription::with('subscription')
                            ->find($get('customer_subscription_id'));

                        if (! $sub || ! $sub->subscription) {
                            return null;
                        }

                        $price = (float) $sub->subscription->price;
                        $half = round($price / 2, 2);

                        return "Allowed: 50% ({$half}) or full ({$price})";
                    }),

                Select::make('status')
                    ->options([
                        'paid' => 'Paid',
                        'partial' => 'Partial',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                    ])
                    ->default('paid')
                    ->required(),

                Select::make('method')
                    ->options(\App\Enums\PaymentMethod::options())
                    ->required(),

                Textarea::make('description')
                    ->placeholder('payment description')
                    ->rows(3)
                    ->required()
            ]);
    }
}
