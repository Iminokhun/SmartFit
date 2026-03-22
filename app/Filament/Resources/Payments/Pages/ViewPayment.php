<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    public function getTitle(): string
    {
        $customer = $this->record->customer?->full_name ?? 'Payment';
        $amount   = number_format($this->record->amount ?? 0, 0, '.', ' ');

        return "{$customer} — {$amount} UZS";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->url(PaymentResource::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
            EditAction::make(),
            DeleteAction::make()
                ->visible(fn () => auth()->user()?->can('delete', $this->record)),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->columns(['default' => 1, 'lg' => 3])
                ->schema([
                    Group::make([
                        Section::make('Payment Info')
                            ->icon('heroicon-m-credit-card')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextEntry::make('customer.full_name')
                                        ->label('Customer')
                                        ->icon('heroicon-m-user')
                                        ->weight('bold')
                                        ->color('primary')
                                        ->size('lg')
                                        ->url(fn () => $this->record->customer_id
                                            ? CustomerResource::getUrl('view', ['record' => $this->record->customer_id])
                                            : null)
                                        ->openUrlInNewTab(),

                                    TextEntry::make('customerSubscription.subscription.name')
                                        ->label('Subscription')
                                        ->icon('heroicon-m-ticket')
                                        ->weight('bold')
                                        ->placeholder('—')
                                        ->size('lg')
                                        ->url(fn () => $this->record->customerSubscription?->subscription_id
                                            ? SubscriptionResource::getUrl('view', ['record' => $this->record->customerSubscription->subscription_id])
                                            : null)
                                        ->openUrlInNewTab(),
                                ]),

                                Grid::make(3)->schema([
                                    TextEntry::make('amount')
                                        ->label('Amount')
                                        ->money('UZS')
                                        ->icon('heroicon-m-banknotes')
                                        ->weight('bold')
                                        ->color('success'),

                                    TextEntry::make('status')
                                        ->label('Status')
                                        ->badge()
                                        ->color(fn ($state) => match ($state) {
                                            'paid'    => 'success',
                                            'partial' => 'warning',
                                            'pending' => 'info',
                                            'failed'  => 'danger',
                                            default   => 'gray',
                                        })
                                        ->formatStateUsing(fn ($state) => ucfirst((string) $state)),

                                    TextEntry::make('method')
                                        ->label('Method')
                                        ->badge()
                                        ->color(fn ($state) => match ($state instanceof \App\Enums\PaymentMethod ? $state->value : (string) $state) {
                                            'cash'   => 'success',
                                            'card'   => 'info',
                                            'online' => 'primary',
                                            default  => 'gray',
                                        })
                                        ->formatStateUsing(fn ($state) => ucfirst($state instanceof \App\Enums\PaymentMethod ? $state->value : (string) $state)),
                                ]),
                            ]),

                        Section::make('Description')
                            ->icon('heroicon-m-chat-bubble-left-ellipsis')
                            ->schema([
                                TextEntry::make('description')
                                    ->label('')
                                    ->placeholder('No description provided.')
                                    ->columnSpanFull(),
                            ]),
                    ])
                        ->columnSpan(['default' => 1, 'lg' => 2]),

                    Group::make([
                        Section::make('Timestamps')
                            ->icon('heroicon-m-clock')
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-m-calendar'),

                                TextEntry::make('updated_at')
                                    ->label('Updated')
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-m-pencil-square'),
                            ]),

                        Section::make('Gateway')
                            ->icon('heroicon-m-globe-alt')
                            ->visible(fn () => $this->record->telegram_payment_charge_id || $this->record->provider_payment_charge_id)
                            ->schema([
                                TextEntry::make('telegram_payment_charge_id')
                                    ->label('Telegram charge ID')
                                    ->placeholder('—')
                                    ->copyable(),

                                TextEntry::make('provider_payment_charge_id')
                                    ->label('Provider charge ID')
                                    ->placeholder('—')
                                    ->copyable(),
                            ]),
                    ])
                        ->columnSpan(['default' => 1, 'lg' => 1]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
