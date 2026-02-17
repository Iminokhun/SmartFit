<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\CustomerSubscription;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SubscriptionsClientsSubscriptionsChart extends ApexChartWidget
{
    protected static ?string $chartId = 'subscriptionsClientsVsSubscriptions';
    protected static ?string $heading = null;

    public ?string $from = null;
    public ?string $until = null;
    public ?int $activityId = null;
    public ?string $paymentMethod = null;
    public ?string $paymentStatus = null;

    protected function getOptions(): array
    {
        [$from, $until] = $this->resolveDateRange();
        $revenueStatuses = $this->resolveRevenueStatuses();

        $customersRows = Payment::query()
            ->selectRaw("DATE(payments.created_at) as date, COUNT(DISTINCT payments.customer_id) as customers")
            ->join('customer_subscriptions', 'customer_subscriptions.id', '=', 'payments.customer_subscription_id')
            ->join('subscriptions', 'subscriptions.id', '=', 'customer_subscriptions.subscription_id')
            ->whereBetween('payments.created_at', [$from, $until])
            ->whereIn('payments.status', $revenueStatuses)
            ->when($this->paymentMethod, fn (Builder $query) => $query->where('payments.method', $this->paymentMethod))
            ->when($this->paymentStatus, fn (Builder $query) => $query->where('payments.status', $this->paymentStatus))
            ->when($this->activityId, fn (Builder $query) => $query->where('subscriptions.activity_id', $this->activityId))
            ->groupBy(DB::raw('DATE(payments.created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $subscriptionsRows = CustomerSubscription::query()
            ->selectRaw("DATE(start_date) as date, COUNT(*) as subscriptions")
            ->whereBetween('start_date', [$from->toDateString(), $until->toDateString()])
            ->when($this->activityId, function (Builder $query) {
                $query->whereHas('subscription', function (Builder $subQuery) {
                    $subQuery->where('activity_id', $this->activityId);
                });
            })
            ->groupBy(DB::raw('DATE(start_date)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $customersSeries = [];
        $subscriptionsSeries = [];
        $cursor = $from->copy();
        while ($cursor->lte($until)) {
            $date = $cursor->toDateString();
            $labels[] = $cursor->format('M d');
            $customersSeries[] = (int) ($customersRows[$date]->customers ?? 0);
            $subscriptionsSeries[] = (int) ($subscriptionsRows[$date]->subscriptions ?? 0);
            $cursor->addDay();
        }

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 280,
                'toolbar' => ['show' => false],
                'fontFamily' => 'Manrope, Segoe UI, Helvetica Neue, Arial, sans-serif',
                'foreColor' => '#64748b',
            ],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 6,
                    'columnWidth' => '45%',
                ],
            ],
            'series' => [
                ['name' => 'Customers', 'data' => $customersSeries],
                ['name' => 'Subscriptions', 'data' => $subscriptionsSeries],
            ],
            'colors' => ['#be123c', '#2563eb'],
            'dataLabels' => ['enabled' => false],
            'grid' => [
                'borderColor' => '#e2e8f0',
                'strokeDashArray' => 4,
            ],
            'xaxis' => [
                'categories' => $labels,
                'labels' => ['rotate' => -45],
                'axisBorder' => ['show' => false],
                'axisTicks' => ['show' => false],
            ],
            'tooltip' => ['shared' => true, 'intersect' => false],
            'legend' => [
                'position' => 'top',
                'horizontalAlign' => 'left',
                'fontSize' => '12px',
            ],
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
}
