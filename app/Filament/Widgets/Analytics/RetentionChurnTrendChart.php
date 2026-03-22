<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Customer;
use App\Models\CustomerSubscription;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RetentionChurnTrendChart extends ApexChartWidget
{
    protected static ?string $chartId = 'retentionChurnTrendChart';
    protected static ?string $heading = null;

    public ?string $from  = null;
    public ?string $until = null;
    public ?int $activityId = null;

    protected function getOptions(): array
    {
        [$from, $until] = $this->resolveDateRange();

        $labels        = [];
        $churnedSeries = [];
        $retainedSeries = [];

        $cursor = $from->copy()->startOfMonth();
        $end    = $until->copy()->endOfMonth();

        while ($cursor->lte($end)) {
            $monthFrom  = $cursor->copy()->startOfMonth();
            $monthUntil = $cursor->copy()->endOfMonth();

            $expiringIds = CustomerSubscription::query()
                ->whereBetween('end_date', [$monthFrom->toDateString(), $monthUntil->toDateString()])
                ->when($this->activityId, fn ($q) => $q->whereHas('subscription', fn ($s) => $s->where('activity_id', $this->activityId)))
                ->distinct()
                ->pluck('customer_id');

            $churned = 0;
            if ($expiringIds->isNotEmpty()) {
                $churned = Customer::query()
                    ->whereIn('id', $expiringIds)
                    ->whereDoesntHave('subscriptions', fn ($q) =>
                        $q->whereIn('status', ['active', 'pending'])
                          ->whereDate('end_date', '>=', today())
                    )
                    ->count();
            }

            $retained = max(0, $expiringIds->count() - $churned);

            $labels[]         = $cursor->format('M Y');
            $churnedSeries[]  = $churned;
            $retainedSeries[] = $retained;

            $cursor->addMonth();
        }

        return [
            'chart' => [
                'type'       => 'line',
                'height'     => 280,
                'toolbar'    => ['show' => false],
                'fontFamily' => 'Manrope, Segoe UI, Helvetica Neue, Arial, sans-serif',
                'foreColor'  => '#64748b',
            ],
            'series' => [
                ['name' => 'Churned',  'data' => $churnedSeries],
                ['name' => 'Retained', 'data' => $retainedSeries],
            ],
            'colors' => ['#ef4444', '#22c55e'],
            'dataLabels' => ['enabled' => false],
            'stroke' => ['curve' => 'smooth', 'width' => 3],
            'grid' => [
                'borderColor'    => '#e2e8f0',
                'strokeDashArray' => 4,
            ],
            'xaxis' => [
                'categories' => $labels,
                'labels'     => ['rotate' => -45],
                'axisBorder' => ['show' => false],
                'axisTicks'  => ['show' => false],
            ],
            'tooltip' => ['shared' => true, 'intersect' => false],
            'legend'  => [
                'position'        => 'top',
                'horizontalAlign' => 'left',
                'fontSize'        => '12px',
            ],
        ];
    }

    private function resolveDateRange(): array
    {
        $from  = $this->from  ? Carbon::parse($this->from)  : Carbon::today();
        $until = $this->until ? Carbon::parse($this->until) : Carbon::today();

        if ($from->gt($until)) {
            [$from, $until] = [$until, $from];
        }

        return [$from->startOfDay(), $until->endOfDay()];
    }
}
