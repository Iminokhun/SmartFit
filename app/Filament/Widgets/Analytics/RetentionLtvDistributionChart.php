<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Customer;
use App\Models\CustomerSubscription;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RetentionLtvDistributionChart extends ApexChartWidget
{
    protected static ?string $chartId = 'retentionLtvDistributionChart';
    protected static ?string $heading = null;

    public ?string $from         = null;
    public ?string $until        = null;
    public array   $activityIds  = [];

    protected function getOptions(): array
    {
        $from  = $this->from  ? Carbon::parse($this->from)->startOfDay()  : Carbon::today()->subMonths(5)->startOfMonth()->startOfDay();
        $until = $this->until ? Carbon::parse($this->until)->endOfDay()   : Carbon::today()->endOfMonth()->endOfDay();

        // Get churned customer IDs in period
        $expiringIds = CustomerSubscription::query()
            ->whereBetween('end_date', [$from->toDateString(), $until->toDateString()])
            ->when($this->activityIds, fn ($q) => $q->whereHas('subscription', fn ($s) => $s->whereIn('activity_id', $this->activityIds)))
            ->distinct()
            ->pluck('customer_id');

        $churnedIds = collect();
        if ($expiringIds->isNotEmpty()) {
            $churnedIds = Customer::query()
                ->whereIn('id', $expiringIds)
                ->whereDoesntHave('subscriptions', fn ($q) =>
                    $q->whereIn('status', ['active', 'pending'])
                      ->whereDate('end_date', '>=', today())
                )
                ->pluck('id');
        }

        // Buckets: < 1 month, 1–3 months, 3–6 months, 6–12 months, 12+ months
        $buckets = [
            '< 1 month'   => 0,
            '1–3 months'  => 0,
            '3–6 months'  => 0,
            '6–12 months' => 0,
            '12+ months'  => 0,
        ];

        if ($churnedIds->isNotEmpty()) {
            $lifetimes = CustomerSubscription::query()
                ->whereIn('customer_id', $churnedIds)
                ->selectRaw('customer_id, MIN(start_date) as first_date, MAX(end_date) as last_date')
                ->groupBy('customer_id')
                ->get();

            foreach ($lifetimes as $row) {
                $months = (int) Carbon::parse($row->first_date)->diffInMonths(Carbon::parse($row->last_date));

                if ($months < 1) {
                    $buckets['< 1 month']++;
                } elseif ($months < 3) {
                    $buckets['1–3 months']++;
                } elseif ($months < 6) {
                    $buckets['3–6 months']++;
                } elseif ($months < 12) {
                    $buckets['6–12 months']++;
                } else {
                    $buckets['12+ months']++;
                }
            }
        }

        // Color: early churn = red, long-term = green
        $colors = ['#f43f5e', '#fb923c', '#facc15', '#34d399', '#10b981'];

        return [
            'chart' => [
                'type'       => 'bar',
                'height'     => 280,
                'toolbar'    => ['show' => false],
                'fontFamily' => 'Manrope, Segoe UI, Helvetica Neue, Arial, sans-serif',
                'foreColor'  => '#64748b',
            ],
            'series' => [
                [
                    'name' => 'Churned Clients',
                    'data' => array_values($buckets),
                ],
            ],
            'colors'     => ['#6366f1'],
            'dataLabels' => [
                'enabled' => true,
                'style'   => ['fontSize' => '12px', 'fontWeight' => '600'],
            ],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 4,
                    'columnWidth'  => '55%',
                    'distributed'  => true,
                ],
            ],
            'colors'  => $colors,
            'xaxis'   => [
                'categories' => array_keys($buckets),
                'axisBorder' => ['show' => false],
                'axisTicks'  => ['show' => false],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => ['fontSize' => '12px'],
                ],
            ],
            'grid' => [
                'borderColor'     => '#e2e8f0',
                'strokeDashArray' => 4,
            ],
            'legend'  => ['show' => false],
            'tooltip' => [],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
{
    tooltip: {
        y: { title: { formatter: function() { return 'Clients:'; } } }
    }
}
JS);
    }
}
