<?php

namespace App\Filament\Pages\Analytics;

use App\Enums\PaymentMethod;
use App\Models\Activity;
use App\Models\CustomerSubscription;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class Subscriptions extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
    protected static string|null|\UnitEnum $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 2;
    protected static ?string $title = 'Subscription Analytics';
    protected static ?string $navigationLabel = 'Subscriptions';
    protected static ?string $slug = 'analytics/subscriptions';

    protected string $view = 'filament.pages.analytics.subscriptions';

    public ?array $data = [];

    public function mount(): void
    {
        $today = Carbon::today();

        $this->form->fill([
            'period'        => 'month',
            'from'          => $today->copy()->startOfMonth()->toDateString(),
            'until'         => $today->copy()->endOfMonth()->toDateString(),
            'activityId'    => [],
            'paymentMethod' => [],
            'paymentStatus' => [],
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Grid::make(['default' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        Select::make('period')
                            ->label('Period')
                            ->options([
                                'today' => 'Today',
                                'week'  => 'This week',
                                'month' => 'This month',
                                'range' => 'Custom range',
                            ])
                            ->default('month')
                            ->live()
                            ->afterStateUpdated(fn () => $this->syncPeriodDates()),

                        DatePicker::make('from')
                            ->label('From')
                            ->live()
                            ->disabled(fn () => ($this->data['period'] ?? 'month') !== 'range'),

                        DatePicker::make('until')
                            ->label('Until')
                            ->live()
                            ->disabled(fn () => ($this->data['period'] ?? 'month') !== 'range'),

                        Select::make('activityId')
                            ->label('Activity')
                            ->options(fn () => Activity::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->placeholder('All')
                            ->live(),

                        Select::make('paymentMethod')
                            ->label('Payment method')
                            ->options(fn () => PaymentMethod::options())
                            ->multiple()
                            ->placeholder('All')
                            ->live(),

                        Select::make('paymentStatus')
                            ->label('Payment status')
                            ->options([
                                'paid'    => 'Paid',
                                'partial' => 'Partial',
                                'pending' => 'Pending',
                                'failed'  => 'Failed',
                            ])
                            ->multiple()
                            ->placeholder('Paid + Partial')
                            ->live(),
                    ]),
            ])
            ->statePath('data');
    }

    public function resetFilters(): void
    {
        $today = Carbon::today();

        $this->form->fill([
            'period'        => 'month',
            'from'          => $today->copy()->startOfMonth()->toDateString(),
            'until'         => $today->copy()->endOfMonth()->toDateString(),
            'activityId'    => [],
            'paymentMethod' => [],
            'paymentStatus' => [],
        ]);
    }

    protected function getViewData(): array
    {
        $activityId    = !empty($this->data['activityId'])    ? array_map('intval', (array) $this->data['activityId']) : [];
        $paymentMethod = !empty($this->data['paymentMethod']) ? (array) $this->data['paymentMethod']                    : [];
        $paymentStatus = !empty($this->data['paymentStatus']) ? (array) $this->data['paymentStatus']                    : [];

        [$from, $until] = $this->resolveDateRange();
        $revenueStatuses = $this->resolveRevenueStatuses($paymentStatus);

        $paymentsBase = Payment::query()
            ->whereBetween('created_at', [$from, $until])
            ->when($paymentMethod, fn (Builder $query) => $query->whereIn('method', $paymentMethod))
            ->when($paymentStatus, fn (Builder $query) => $query->whereIn('status', $paymentStatus))
            ->when($activityId, function (Builder $query) use ($activityId) {
                $query->whereHas('customerSubscription.subscription', function (Builder $subQuery) use ($activityId) {
                    $subQuery->whereIn('activity_id', $activityId);
                });
            });

        $revenue = 0.0;
        $payingCustomers = 0;
        $topPlans = collect();

        if (! empty($revenueStatuses)) {
            $revenue = (clone $paymentsBase)
                ->whereIn('status', $revenueStatuses)
                ->sum('amount');

            $payingCustomers = (clone $paymentsBase)
                ->whereIn('status', $revenueStatuses)
                ->distinct('customer_id')
                ->count('customer_id');

            $topPlans = Payment::query()
                ->selectRaw('subscriptions.id as id, subscriptions.name as name, activities.name as activity_name, SUM(payments.amount) as total, COUNT(DISTINCT customer_subscriptions.id) as sales')
                ->join('customer_subscriptions', 'customer_subscriptions.id', '=', 'payments.customer_subscription_id')
                ->join('subscriptions', 'subscriptions.id', '=', 'customer_subscriptions.subscription_id')
                ->join('activities', 'activities.id', '=', 'subscriptions.activity_id')
                ->whereBetween('payments.created_at', [$from, $until])
                ->whereIn('payments.status', $revenueStatuses)
                ->when($paymentMethod, fn (Builder $query) => $query->whereIn('payments.method', $paymentMethod))
                ->when($activityId, fn (Builder $query) => $query->whereIn('subscriptions.activity_id', $activityId))
                ->groupBy('subscriptions.id', 'subscriptions.name', 'activities.name')
                ->orderByDesc('total')
                ->limit(10)
                ->get();

            $totalTopRevenue = $topPlans->sum('total');
            $topPlans = $topPlans->map(function ($row) use ($totalTopRevenue) {
                $row->share     = $totalTopRevenue > 0 ? round((float) $row->total / (float) $totalTopRevenue * 100, 1) : 0.0;
                $row->avg_price = (int) $row->sales > 0 ? (int) round((float) $row->total / (int) $row->sales) : 0;
                return $row;
            });
        }

        $subscriptionsBase = CustomerSubscription::query()
            ->whereBetween('start_date', [$from->toDateString(), $until->toDateString()])
            ->when($activityId, function (Builder $query) use ($activityId) {
                $query->whereHas('subscription', function (Builder $subQuery) use ($activityId) {
                    $subQuery->whereIn('activity_id', $activityId);
                });
            });

        $newSubscriptions = (clone $subscriptionsBase)->count();
        $activeSubscriptions = CustomerSubscription::query()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $until->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->when($activityId, function (Builder $query) use ($activityId) {
                $query->whereHas('subscription', function (Builder $subQuery) use ($activityId) {
                    $subQuery->whereIn('activity_id', $activityId);
                });
            })
            ->count();

        $arpu = $payingCustomers > 0 ? $revenue / $payingCustomers : 0;

        // Prev period
        $periodLength = max(1, (int) $from->diffInDays($until) + 1);
        $prevUntil = $from->copy()->subDay()->endOfDay();
        $prevFrom  = $prevUntil->copy()->subDays($periodLength - 1)->startOfDay();

        $prevPaymentsBase = Payment::query()
            ->whereBetween('created_at', [$prevFrom, $prevUntil])
            ->when($paymentMethod, fn (Builder $query) => $query->whereIn('method', $paymentMethod))
            ->when($paymentStatus, fn (Builder $query) => $query->whereIn('status', $paymentStatus))
            ->when($activityId, function (Builder $query) use ($activityId) {
                $query->whereHas('customerSubscription.subscription', function (Builder $subQuery) use ($activityId) {
                    $subQuery->whereIn('activity_id', $activityId);
                });
            });

        $prevRevenue = 0.0;
        $prevPayingCustomers = 0;
        if (! empty($revenueStatuses)) {
            $prevRevenue = (clone $prevPaymentsBase)->whereIn('status', $revenueStatuses)->sum('amount');
            $prevPayingCustomers = (clone $prevPaymentsBase)->whereIn('status', $revenueStatuses)->distinct('customer_id')->count('customer_id');
        }

        $prevNewSubscriptions = CustomerSubscription::query()
            ->whereBetween('start_date', [$prevFrom->toDateString(), $prevUntil->toDateString()])
            ->when($activityId, function (Builder $query) use ($activityId) {
                $query->whereHas('subscription', function (Builder $subQuery) use ($activityId) {
                    $subQuery->where('activity_id', $activityId);
                });
            })
            ->count();

        $prevActiveSubscriptions = CustomerSubscription::query()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $prevUntil->toDateString())
            ->whereDate('end_date', '>=', $prevFrom->toDateString())
            ->when($activityId, function (Builder $query) use ($activityId) {
                $query->whereHas('subscription', function (Builder $subQuery) use ($activityId) {
                    $subQuery->whereIn('activity_id', $activityId);
                });
            })
            ->count();

        $prevArpu = $prevPayingCustomers > 0 ? $prevRevenue / $prevPayingCustomers : 0;

        $deltaNum = fn($cur, $prev): ?string =>
            $prev > 0 ? abs(round(($cur - $prev) / $prev * 100, 1)) . '%' : null;
        $dir = fn($cur, $prev): string => $cur >= $prev ? 'up' : 'down';

        $cards = [
            [
                'label'      => 'Revenue',
                'value'      => number_format((float) $revenue, 0, '.', ' '),
                'suffix'     => ' UZS',
                'delta'      => $deltaNum($revenue, $prevRevenue),
                'deltaLabel' => 'vs prev period',
                'direction'  => $dir($revenue, $prevRevenue),
                'sentiment'  => 'positive',
            ],
            [
                'label'      => 'New Subscriptions',
                'value'      => $newSubscriptions,
                'suffix'     => null,
                'delta'      => $deltaNum($newSubscriptions, $prevNewSubscriptions),
                'deltaLabel' => 'vs prev period',
                'direction'  => $dir($newSubscriptions, $prevNewSubscriptions),
                'sentiment'  => 'positive',
            ],
            [
                'label'      => 'Active Subscriptions',
                'value'      => $activeSubscriptions,
                'suffix'     => null,
                'delta'      => $deltaNum($activeSubscriptions, $prevActiveSubscriptions),
                'deltaLabel' => 'vs prev period',
                'direction'  => $dir($activeSubscriptions, $prevActiveSubscriptions),
                'sentiment'  => 'neutral',
            ],
            [
                'label'      => 'ARPU',
                'value'      => number_format((float) $arpu, 0, '.', ' '),
                'suffix'     => ' UZS',
                'delta'      => $deltaNum($arpu, $prevArpu),
                'deltaLabel' => 'vs prev period',
                'direction'  => $dir($arpu, $prevArpu),
                'sentiment'  => 'positive',
            ],
        ];

        return [
            'cards'      => $cards,
            'topPlans'   => $topPlans,
            'rangeLabel' => $from->toDateString() . ' → ' . $until->toDateString(),
            'from'       => $from->toDateString(),
            'until'      => $until->toDateString(),
            'activityId'    => $activityId,
            'paymentMethod' => $paymentMethod,
            'paymentStatus' => $paymentStatus,
        ];
    }

    private function resolveDateRange(): array
    {
        $from  = isset($this->data['from'])  ? Carbon::parse($this->data['from'])  : Carbon::today();
        $until = isset($this->data['until']) ? Carbon::parse($this->data['until']) : Carbon::today();

        if ($from->gt($until)) {
            [$from, $until] = [$until, $from];
        }

        return [$from->startOfDay(), $until->endOfDay()];
    }

    private function resolveRevenueStatuses(array $paymentStatus): array
    {
        $allowed = ['paid', 'partial'];
        if (!empty($paymentStatus)) {
            return array_values(array_intersect($allowed, $paymentStatus));
        }

        return $allowed;
    }

    private function syncPeriodDates(): void
    {
        $today  = Carbon::today();
        $period = $this->data['period'] ?? 'month';

        switch ($period) {
            case 'today':
                $this->data['from']  = $today->toDateString();
                $this->data['until'] = $today->toDateString();
                break;
            case 'week':
                $this->data['from']  = $today->copy()->startOfWeek()->toDateString();
                $this->data['until'] = $today->copy()->endOfWeek()->toDateString();
                break;
            case 'range':
                $this->data['from']  ??= $today->toDateString();
                $this->data['until'] ??= $today->toDateString();
                break;
            case 'month':
            default:
                $this->data['from']  = $today->copy()->startOfMonth()->toDateString();
                $this->data['until'] = $today->copy()->endOfMonth()->toDateString();
                break;
        }
    }
}
