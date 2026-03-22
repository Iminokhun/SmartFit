<?php

namespace App\Filament\Resources\Subscriptions\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Models\Payment;
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
            DeleteAction::make()
                ->visible(fn () => auth()->user()?->can('delete', $this->record)),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columns([
                        'default' => 1,
                        'lg' => 3,
                    ])
                    ->schema([
                        Group::make([
                            Section::make('Subscription Info')
                                ->icon('heroicon-m-information-circle')
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextEntry::make('name')
                                            ->label('Name')
                                            ->icon('heroicon-m-ticket')
                                            ->color('primary')
                                            ->weight('bold')
                                            ->size('lg'),

                                        TextEntry::make('activity.name')
                                            ->label('Activity')
                                            ->icon('heroicon-m-bolt')
                                            ->weight('bold')
                                            ->size('lg'),

                                        TextEntry::make('trainer.full_name')
                                            ->label('Trainer')
                                            ->icon('heroicon-m-user')
                                            ->weight('bold')
                                            ->placeholder('-')
                                            ->size('lg'),
                                    ]),

                                    TextEntry::make('description')
                                        ->label('Description')
                                        ->placeholder('No description provided.')
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Pricing & Revenue')
                                ->icon('heroicon-m-banknotes')
                                ->columns(4)
                                ->schema([
                                    TextEntry::make('price')
                                        ->label('Price')
                                        ->money('UZS')
                                        ->weight('bold'),

                                    TextEntry::make('discount')
                                        ->label('Discount')
                                        ->suffix('%')
                                        ->color('warning')
                                        ->weight('bold'),

                                    TextEntry::make('final_price')
                                        ->label('Final price')
                                        ->money('UZS')
                                        ->color('success')
                                        ->state(function ($record) {
                                            $price = $record->price ?? 0;
                                            $discount = $record->discount ?? 0;
                                            return max(0, $price - ($price * $discount / 100));
                                        })
                                        ->weight('bold'),

                                    TextEntry::make('total_revenue')
                                        ->label('Total revenue')
                                        ->money('UZS')
                                        ->icon('heroicon-m-chart-bar')
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

                                                        Section::make('Available Schedule')
                                ->icon('heroicon-m-calendar-days')
                                ->schema([
                                    RepeatableEntry::make('activity.schedules')
                                        ->label('')
                                        ->schema([
                                            TextEntry::make('days_of_week')
                                                ->label('Days')
                                                ->state(fn ($record) => self::formatWeekdays($record->days_of_week))
                                                ->badge(),

                                            TextEntry::make('time_range')
                                                ->label('Time')
                                                ->state(fn ($record) => $record->time_range ?? '�')
                                                ->badge(),

                                            TextEntry::make('staff.full_name')
                                                ->label('Trainer')
                                                ->state(fn ($record) => $record->staff?->full_name ?? '-')
                                                ->badge(),

                                            TextEntry::make('hall.name')
                                                ->label('Hall')
                                                ->state(fn ($record) => $record->hall?->name ?? '-')
                                                ->badge(),

                                            TextEntry::make('max_participants')
                                                ->label('Capacity')
                                                ->state(fn ($record) => $record->max_participants ?? '')
                                                ->badge(),
                                        ])
                                        ->columns(5)
                                        ->columnSpanFull(),
                                ]),
                            Section::make('Customers')
                                ->icon('heroicon-m-users')
                                ->schema([
                                    RepeatableEntry::make('customers')
                                        ->label('')
                                        ->schema([
                                            TextEntry::make('customer.full_name')
                                                ->label('Customer')
                                                ->weight('bold')
                                                ->color('primary')
                                                ->url(fn ($record) => CustomerResource::getUrl('view', ['record' => $record->customer_id]))
                                                ->openUrlInNewTab(),

                                            TextEntry::make('status')
                                                ->badge()
                                                ->color(fn ($state) => match ($state) {
                                                    'active' => 'success',
                                                    'expired' => 'danger',
                                                    'frozen' => 'warning',
                                                    default => 'gray',
                                                })
                                                ->formatStateUsing(fn ($state) => ucfirst((string) $state)),

                                            TextEntry::make('start_date')->label('Start')->date(),
                                            TextEntry::make('end_date')->label('End')->date(),

                                            TextEntry::make('remaining_visits')
                                                ->label('Visits left')
                                                ->badge()
                                                ->color('info')
                                                ->state(fn ($record) => $record->remaining_visits ?? 'Unlimited'),

                                            TextEntry::make('paid_amount')->label('Paid')->money('UZS'),
                                            TextEntry::make('debt')->label('Debt')->money('UZS')->color('danger'),

                                            TextEntry::make('payment_status')
                                                ->label('Payment')
                                                ->badge()
                                                ->color(fn ($state) => match ($state) {
                                                    'paid' => 'success',
                                                    'partial' => 'warning',
                                                    default => 'danger',
                                                })
                                                ->formatStateUsing(fn ($state) => ucfirst((string) $state)),
                                        ])
                                        ->columns(8)
                                        ->columnSpanFull(),
                                ]),
                        ])
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 2,
                            ]),

                        Group::make([
                            Section::make('Capacity & Limits')
                                ->icon('heroicon-m-scale')
                                ->schema([
                                    TextEntry::make('limit_summary')
                                        ->label('Current Capacity')
                                        ->state(fn ($record) => $record->capacityLabel())
                                        ->badge()
                                        ->color(fn ($record) => $record->capacityColor()),

                                    TextEntry::make('duration_days')
                                        ->label('Duration')
                                        ->icon('heroicon-m-calendar')
                                        ->suffix(' days')
                                        ->weight('bold'),

                                    TextEntry::make('visits_limit')
                                        ->label('Visit limit')
                                        ->icon('heroicon-m-arrow-path')
                                        ->state(fn ($record) => $record->visits_limit ?? 'Unlimited')
                                        ->weight('bold'),
                                ]),

                            Section::make('Access Rules')
                                ->icon('heroicon-m-shield-check')
                                ->schema([
                                    TextEntry::make('allowed_weekdays')
                                        ->label('Allowed days')
                                        ->icon('heroicon-m-calendar-days')
                                        ->state(fn ($record) => self::formatWeekdays($record->allowed_weekdays))
                                        ->badge()
                                        ->color('info'),

                                    TextEntry::make('time_window')
                                        ->label('Allowed time')
                                        ->icon('heroicon-m-clock')
                                        ->state(fn ($record) => self::formatTimeWindow($record->time_from, $record->time_to))
                                        ->badge()
                                        ->color('info'),

                                    TextEntry::make('max_checkins_per_day')
                                        ->label('Daily check-ins')
                                        ->state(fn ($record) => $record->max_checkins_per_day ? (string) $record->max_checkins_per_day : 'Unlimited')
                                        ->badge(),

                                    TextEntry::make('freeze_days_limit')
                                        ->label('Freeze limit')
                                        ->icon('heroicon-m-cloud')
                                        ->state(fn ($record) => (string) ((int) ($record->freeze_days_limit ?? 0)) . ' days')
                                        ->badge(),
                                ]),
                        ])
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 1,
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function formatWeekdays(mixed $value): string
    {
        if (! is_array($value) || count($value) === 0) {
            return 'All days';
        }

        $map = [
            1 => 'Mon',
            2 => 'Tue',
            3 => 'Wed',
            4 => 'Thu',
            5 => 'Fri',
            6 => 'Sat',
            7 => 'Sun',
        ];

        $labels = [];
        foreach (array_map('intval', $value) as $day) {
            if (isset($map[$day])) {
                $labels[] = $map[$day];
            }
        }

        return count($labels) > 0 ? implode(', ', $labels) : 'All days';
    }

    private static function formatTimeWindow(mixed $from, mixed $to): string
    {
        if (! $from || ! $to) {
            return 'Any time';
        }

        $format = fn ($t) => $t instanceof \DateTimeInterface ? $t->format('H:i') : substr((string) $t, 0, 5);

        return $format($from) . ' - ' . $format($to);
    }
}

