<?php

namespace App\Filament\Pages\Analytics;

use App\Enums\PaymentMethod;
use App\Models\Activity;
use App\Models\CustomerSubscription;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class Subscriptions extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
    protected static string|null|\UnitEnum $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 2;
    protected static ?string $title = 'Subscription Analytics';
    protected static ?string $navigationLabel = 'Subscriptions';
    protected static ?string $slug = 'analytics/subscriptions';

    protected string $view = 'filament.pages.analytics.subscriptions';

    public string $period = 'month';
    public ?string $from = null;
    public ?string $until = null;
    public ?int $activityId = null;
    public ?string $paymentMethod = null;
    public ?string $paymentStatus = null;

    public function mount(): void
    {
        $this->syncPeriodDates();
    }

    public function updatedPeriod(): void
    {
        $this->syncPeriodDates();
    }

    protected function getViewData(): array
    {
        [$from, $until] = $this->resolveDateRange();
        $revenueStatuses = $this->resolveRevenueStatuses();

        $paymentsBase = Payment::query()
            ->whereBetween('created_at', [$from, $until])
            ->when($this->paymentMethod, fn (Builder $query) => $query->where('method', $this->paymentMethod))
            ->when($this->paymentStatus, fn (Builder $query) => $query->where('status', $this->paymentStatus))
            ->when($this->activityId, function (Builder $query) {
                $query->whereHas('customerSubscription.subscription', function (Builder $subQuery) {
                    $subQuery->where('activity_id', $this->activityId);
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
                ->selectRaw('subscriptions.id as id, subscriptions.name as name, SUM(payments.amount) as total, COUNT(DISTINCT customer_subscriptions.id) as sales')
                ->join('customer_subscriptions', 'customer_subscriptions.id', '=', 'payments.customer_subscription_id')
                ->join('subscriptions', 'subscriptions.id', '=', 'customer_subscriptions.subscription_id')
                ->whereBetween('payments.created_at', [$from, $until])
                ->whereIn('payments.status', $revenueStatuses)
                ->when($this->paymentMethod, fn (Builder $query) => $query->where('payments.method', $this->paymentMethod))
                ->when($this->activityId, fn (Builder $query) => $query->where('subscriptions.activity_id', $this->activityId))
                ->groupBy('subscriptions.id', 'subscriptions.name')
                ->orderByDesc('total')
                ->limit(10)
                ->get();

        }

        $subscriptionsBase = CustomerSubscription::query()
            ->whereBetween('start_date', [$from->toDateString(), $until->toDateString()])
            ->when($this->activityId, function (Builder $query) {
                $query->whereHas('subscription', function (Builder $subQuery) {
                    $subQuery->where('activity_id', $this->activityId);
                });
            });

        $newSubscriptions = (clone $subscriptionsBase)->count();
        $activeSubscriptions = CustomerSubscription::query()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $until->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->when($this->activityId, function (Builder $query) {
                $query->whereHas('subscription', function (Builder $subQuery) {
                    $subQuery->where('activity_id', $this->activityId);
                });
            })
            ->count();

        $arpu = $payingCustomers > 0 ? $revenue / $payingCustomers : 0;

        return [
            'periodOptions' => [
                'today' => 'Today',
                'week' => 'This week',
                'month' => 'This month',
                'range' => 'Custom range',
            ],
            'activities' => Activity::query()->orderBy('name')->pluck('name', 'id')->all(),
            'paymentMethods' => PaymentMethod::options(),
            'paymentStatuses' => [
                'paid' => 'Paid',
                'partial' => 'Partial',
                'pending' => 'Pending',
                'failed' => 'Failed',
            ],
            'metrics' => [
                'revenue' => $revenue,
                'newSubscriptions' => $newSubscriptions,
                'activeSubscriptions' => $activeSubscriptions,
                'arpu' => $arpu,
            ],
            'topPlans' => $topPlans,
            'rangeLabel' => $from->toDateString() . ' → ' . $until->toDateString(),
        ];
    }

    private function resolveDateRange(): array
    {
        $from = $this->from ? Carbon::parse($this->from) : Carbon::today();
        $until = $this->until ? Carbon::parse($this->until) : Carbon::today();

        if ($from->gt($until)) {
            [$from, $until] = [$until, $from];
        }

        return [$from->startOfDay(), $until->endOfDay()];
    }

    private function resolveRevenueStatuses(): array
    {
        $allowed = ['paid', 'partial'];
        if ($this->paymentStatus) {
            return array_values(array_intersect($allowed, [$this->paymentStatus]));
        }

        return $allowed;
    }

    private function syncPeriodDates(): void
    {
        $today = Carbon::today();

        switch ($this->period) {
            case 'today':
                $this->from = $today->toDateString();
                $this->until = $today->toDateString();
                break;
            case 'week':
                $this->from = $today->copy()->startOfWeek()->toDateString();
                $this->until = $today->copy()->endOfWeek()->toDateString();
                break;
            case 'range':
                $this->from ??= $today->toDateString();
                $this->until ??= $today->toDateString();
                break;
            case 'month':
            default:
                $this->from = $today->copy()->startOfMonth()->toDateString();
                $this->until = $today->copy()->endOfMonth()->toDateString();
                break;
        }
    }
}
