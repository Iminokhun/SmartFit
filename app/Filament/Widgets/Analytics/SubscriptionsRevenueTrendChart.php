<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SubscriptionsRevenueTrendChart extends ApexChartWidget
{
    protected static ?string $chartId = 'subscriptionsRevenueTrend';
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

        $rows = Payment::query()
            ->selectRaw("DATE(payments.created_at) as date, SUM(payments.amount) as revenue, COUNT(DISTINCT payments.customer_id) as customers")
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

        $labels = [];
        $revenueSeries = [];
        $customersSeries = [];
        $cursor = $from->copy();
        while ($cursor->lte($until)) {
            $date = $cursor->toDateString();
            $labels[] = $cursor->format('M d');
            $revenueSeries[] = (float) ($rows[$date]->revenue ?? 0);
            $customersSeries[] = (int) ($rows[$date]->customers ?? 0);
            $cursor->addDay();
        }

        return [
            'chart' => [
                'type' => 'area',
                'height' => 280,
                'toolbar' => ['show' => false],
                'fontFamily' => 'Manrope, Segoe UI, Helvetica Neue, Arial, sans-serif',
                'foreColor' => '#64748b',
            ],
            'series' => [
                ['name' => 'Revenue', 'data' => $revenueSeries],
                ['name' => 'Customers', 'data' => $customersSeries],
            ],
            'colors' => ['#2563eb', '#0f766e'],
            'dataLabels' => ['enabled' => false],
            'stroke' => ['curve' => 'smooth', 'width' => 2],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shadeIntensity' => 1,
                    'opacityFrom' => 0.22,
                    'opacityTo' => 0.03,
                    'stops' => [0, 90, 100],
                ],
            ],
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
