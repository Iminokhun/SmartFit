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
                            $set('amount', $sub->subscription->price);
                        }
                    }),

                Placeholder::make('subscription_price')
                    ->label('Subscription Price')
                    ->content(function ( $get) {
                        $sub = CustomerSubscription::with('subscription')
                            ->find($get('customer_subscription_id'));

                        return $sub?->subscription
                            ? number_format($sub->subscription->price, 2)
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

                                $price = $sub->subscription->price;
                                $min = $price * 0.5;
                                $max = $price;

                                if ($value < $min) {
                                    $fail("Minimum payment is 50%: {$min}");
                                }

                                if ($value > $max) {
                                    $fail("Amount cannot exceed full price: {$max}");
                                }
                            };
                        },
                    ])
                    ->helperText(function ($get) {
                        $sub = CustomerSubscription::with('subscription')
                            ->find($get('customer_subscription_id'));

                        if (! $sub || ! $sub->subscription) {
                            return null;
                        }

                        $price = $sub->subscription->price;

                        return "Allowed range: min " . ($price * 0.5) . " — max {$price}";
                    }),

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
