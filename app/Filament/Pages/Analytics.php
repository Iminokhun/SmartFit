<?php

namespace App\Filament\Pages;

use App\Enums\PaymentMethod;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\CustomerSubscription;
use App\Models\Expense;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class Analytics extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedChartBar;
    protected static string|null|\UnitEnum $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Analytics Overview';
    protected static ?string $navigationLabel = 'Overview';

    protected string $view = 'filament.pages.analytics';

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
        $topSubscriptions = collect();
        $topActivities = collect();
        $topCustomers = collect();

        if (! empty($revenueStatuses)) {
            $revenue = (clone $paymentsBase)
                ->whereIn('status', $revenueStatuses)
                ->sum('amount');

            $payingCustomers = (clone $paymentsBase)
                ->whereIn('status', $revenueStatuses)
                ->distinct('customer_id')
                ->count('customer_id');

            $topSubscriptions = Payment::query()
                ->selectRaw('subscriptions.id as id, subscriptions.name as name, SUM(payments.amount) as total')
                ->join('customer_subscriptions', 'customer_subscriptions.id', '=', 'payments.customer_subscription_id')
                ->join('subscriptions', 'subscriptions.id', '=', 'customer_subscriptions.subscription_id')
                ->whereBetween('payments.created_at', [$from, $until])
                ->whereIn('payments.status', $revenueStatuses)
                ->when($this->paymentMethod, fn (Builder $query) => $query->where('payments.method', $this->paymentMethod))
                ->when($this->activityId, fn (Builder $query) => $query->where('subscriptions.activity_id', $this->activityId))
                ->groupBy('subscriptions.id', 'subscriptions.name')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            $topActivities = Payment::query()
                ->selectRaw('activities.id as id, activities.name as name, SUM(payments.amount) as total')
                ->join('customer_subscriptions', 'customer_subscriptions.id', '=', 'payments.customer_subscription_id')
                ->join('subscriptions', 'subscriptions.id', '=', 'customer_subscriptions.subscription_id')
                ->join('activities', 'activities.id', '=', 'subscriptions.activity_id')
                ->whereBetween('payments.created_at', [$from, $until])
                ->whereIn('payments.status', $revenueStatuses)
                ->when($this->paymentMethod, fn (Builder $query) => $query->where('payments.method', $this->paymentMethod))
                ->when($this->activityId, fn (Builder $query) => $query->where('activities.id', $this->activityId))
                ->groupBy('activities.id', 'activities.name')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            $topCustomers = Payment::query()
                ->selectRaw('customers.id as id, customers.full_name as name, SUM(payments.amount) as total')
                ->join('customers', 'customers.id', '=', 'payments.customer_id')
                ->join('customer_subscriptions', 'customer_subscriptions.id', '=', 'payments.customer_subscription_id')
                ->join('subscriptions', 'subscriptions.id', '=', 'customer_subscriptions.subscription_id')
                ->whereBetween('payments.created_at', [$from, $until])
                ->whereIn('payments.status', $revenueStatuses)
                ->when($this->paymentMethod, fn (Builder $query) => $query->where('payments.method', $this->paymentMethod))
                ->when($this->activityId, fn (Builder $query) => $query->where('subscriptions.activity_id', $this->activityId))
                ->groupBy('customers.id', 'customers.full_name')
                ->orderByDesc('total')
                ->limit(5)
                ->get();
        }

        $expenses = Expense::query()
            ->whereBetween('expenses_date', [$from->toDateString(), $until->toDateString()])
            ->sum('amount');

        $newCustomers = Customer::query()
            ->whereBetween('created_at', [$from, $until])
            ->count();

        $subscriptionsBase = CustomerSubscription::query()
            ->whereDate('start_date', '<=', $until->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->when($this->activityId, function (Builder $query) {
                $query->whereHas('subscription', function (Builder $subQuery) {
                    $subQuery->where('activity_id', $this->activityId);
                });
            });

        $activeSubscriptions = (clone $subscriptionsBase)
            ->where('status', 'active')
            ->count();

        $debt = (clone $subscriptionsBase)->sum('debt');

        $profit = $revenue - $expenses;
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
                'expenses' => $expenses,
                'profit' => $profit,
                'newCustomers' => $newCustomers,
                'activeSubscriptions' => $activeSubscriptions,
                'arpu' => $arpu,
                'debt' => $debt,
            ],
            'topSubscriptions' => $topSubscriptions,
            'topActivities' => $topActivities,
            'topCustomers' => $topCustomers,
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
