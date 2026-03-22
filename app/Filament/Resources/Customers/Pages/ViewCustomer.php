<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    public function getTitle(): string
    {
        return (string) ($this->record->full_name ?? 'Customer');
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->load([
            'subscriptions.subscription.activity',
            'payments',
            'telegramLink',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->url(CustomerResource::getUrl('index'))
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
                        Section::make('Personal Info')
                            ->icon('heroicon-m-user')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextEntry::make('full_name')
                                        ->label('Name')
                                        ->icon('heroicon-m-user')
                                        ->weight('bold')
                                        ->color('primary')
                                        ->size('lg'),

                                    TextEntry::make('phone')
                                        ->label('Phone')
                                        ->icon('heroicon-m-phone')
                                        ->placeholder('—')
                                        ->copyable(),

                                    TextEntry::make('email')
                                        ->label('Email')
                                        ->icon('heroicon-m-envelope')
                                        ->placeholder('—')
                                        ->copyable(),
                                ]),

                                Grid::make(3)->schema([
                                    TextEntry::make('gender')
                                        ->label('Gender')
                                        ->badge()
                                        ->color(fn ($state) => match ($state) {
                                            'male'   => 'info',
                                            'female' => 'warning',
                                            default  => 'gray',
                                        })
                                        ->formatStateUsing(fn ($state) => ucfirst((string) $state))
                                        ->placeholder('—'),

                                    TextEntry::make('birth_date')
                                        ->label('Birth date')
                                        ->date('d M Y')
                                        ->icon('heroicon-m-cake')
                                        ->placeholder('—'),

                                    TextEntry::make('status')
                                        ->label('Status')
                                        ->badge()
                                        ->color(fn ($state) => match ($state) {
                                            'active'   => 'success',
                                            'inactive' => 'danger',
                                            'frozen'   => 'warning',
                                            default    => 'gray',
                                        })
                                        ->formatStateUsing(fn ($state) => ucfirst((string) $state)),
                                ]),
                            ]),

                        Section::make('Subscriptions')
                            ->icon('heroicon-m-ticket')
                            ->schema([
                                RepeatableEntry::make('subscriptions')
                                    ->label('')
                                    ->schema([
                                        TextEntry::make('subscription.name')
                                            ->label('Subscription')
                                            ->weight('bold')
                                            ->color('primary')
                                            ->url(fn ($record) => $record->subscription_id
                                                ? SubscriptionResource::getUrl('view', ['record' => $record->subscription_id])
                                                : null)
                                            ->openUrlInNewTab(),

                                        TextEntry::make('subscription.activity.name')
                                            ->label('Activity')
                                            ->placeholder('—'),

                                        TextEntry::make('status')
                                            ->label('Status')
                                            ->badge()
                                            ->color(fn ($state) => match ($state) {
                                                'active'  => 'success',
                                                'expired' => 'danger',
                                                'frozen'  => 'warning',
                                                default   => 'gray',
                                            })
                                            ->formatStateUsing(fn ($state) => ucfirst((string) $state)),

                                        TextEntry::make('start_date')->label('Start')->date('d M Y'),
                                        TextEntry::make('end_date')->label('End')->date('d M Y'),

                                        TextEntry::make('remaining_visits')
                                            ->label('Visits left')
                                            ->badge()
                                            ->color('info')
                                            ->state(fn ($record) => $record->remaining_visits ?? 'Unlimited'),

                                        TextEntry::make('payment_status')
                                            ->label('Payment')
                                            ->badge()
                                            ->color(fn ($state) => match ($state) {
                                                'paid'    => 'success',
                                                'partial' => 'warning',
                                                default   => 'danger',
                                            })
                                            ->formatStateUsing(fn ($state) => ucfirst((string) $state)),

                                        TextEntry::make('debt')
                                            ->label('Debt')
                                            ->money('UZS')
                                            ->color('danger'),
                                    ])
                                    ->columns(8)
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Payments')
                            ->icon('heroicon-m-banknotes')
                            ->schema([
                                RepeatableEntry::make('payments')
                                    ->label('')
                                    ->schema([
                                        TextEntry::make('amount')
                                            ->label('Amount')
                                            ->money('UZS')
                                            ->weight('bold')
                                            ->color('success')
                                            ->url(fn ($record) => PaymentResource::getUrl('view', ['record' => $record->id]))
                                            ->openUrlInNewTab(),

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

                                        TextEntry::make('created_at')
                                            ->label('Date')
                                            ->dateTime('d M Y, H:i'),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull(),
                            ]),
                    ])
                        ->columnSpan(['default' => 1, 'lg' => 2]),

                    Group::make([
                        Section::make('Summary')
                            ->icon('heroicon-m-chart-bar')
                            ->schema([
                                TextEntry::make('total_paid')
                                    ->label('Total paid')
                                    ->money('UZS')
                                    ->icon('heroicon-m-banknotes')
                                    ->weight('bold')
                                    ->color('success')
                                    ->state(fn ($record) => $record->payments
                                        ->whereIn('status', ['paid', 'partial'])
                                        ->sum('amount')),

                                TextEntry::make('active_subscriptions')
                                    ->label('Active subscriptions')
                                    ->icon('heroicon-m-ticket')
                                    ->badge()
                                    ->color('success')
                                    ->state(fn ($record) => (string) $record->subscriptions
                                        ->where('status', 'active')
                                        ->count()),

                                TextEntry::make('total_subscriptions')
                                    ->label('Total subscriptions')
                                    ->icon('heroicon-m-rectangle-stack')
                                    ->badge()
                                    ->color('gray')
                                    ->state(fn ($record) => (string) $record->subscriptions->count()),

                                TextEntry::make('total_payments')
                                    ->label('Total payments')
                                    ->icon('heroicon-m-credit-card')
                                    ->badge()
                                    ->color('gray')
                                    ->state(fn ($record) => (string) $record->payments->count()),
                            ]),

                        Section::make('Telegram')
                            ->icon('heroicon-m-chat-bubble-oval-left')
                            ->visible(fn () => $this->record->telegramLink !== null)
                            ->schema([
                                TextEntry::make('telegramLink.telegram_username')
                                    ->label('Username')
                                    ->icon('heroicon-m-at-symbol')
                                    ->placeholder('—')
                                    ->formatStateUsing(fn ($state) => $state ? "@{$state}" : '—'),

                                TextEntry::make('telegramLink.first_name')
                                    ->label('Name')
                                    ->placeholder('—'),

                                TextEntry::make('telegramLink.is_verified')
                                    ->label('Verified')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'danger')
                                    ->formatStateUsing(fn ($state) => $state ? 'Verified' : 'Not verified'),

                                TextEntry::make('telegramLink.linked_at')
                                    ->label('Linked at')
                                    ->dateTime('d M Y, H:i')
                                    ->placeholder('—'),
                            ]),

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
                    ])
                        ->columnSpan(['default' => 1, 'lg' => 1]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
