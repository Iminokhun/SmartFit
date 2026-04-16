<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Customer;
use App\Models\CustomerSubscription;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RetentionLeakyBucketChart extends ApexChartWidget
{
    protected static ?string $chartId = 'retentionLeakyBucketChart';
    protected static ?string $heading = null;

    public ?string $from         = null;
    public ?string $until        = null;
    public array   $activityIds  = [];

    public function mount(): void
    {
        $this->options      = $this->getOptions();
        $this->readyToLoad  = true;
    }

    protected function getOptions(): array
    {
        $from  = $this->from  ? Carbon::parse($this->from)->startOfDay()  : Carbon::today()->subMonths(5)->startOfMonth()->startOfDay();
        $until = $this->until ? Carbon::parse($this->until)->endOfDay()   : Carbon::today()->endOfMonth()->endOfDay();

        $labels        = [];
        $newSeries     = [];
        $churnedSeries = [];
        $netSeries     = [];

        $cursor = $from->copy()->startOfMonth();
        $end    = $until->copy()->endOfMonth();

        while ($cursor->lte($end)) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd   = $cursor->copy()->endOfMonth();

            $newQuery = Customer::query()
                ->whereBetween('created_at', [$monthStart->copy()->startOfDay(), $monthEnd->copy()->endOfDay()]);

            if ($this->activityIds) {
                $newQuery->whereHas('subscriptions', fn ($q) =>
                    $q->whereHas('subscription', fn ($s) => $s->whereIn('activity_id', $this->activityIds))
                );
            }

            $newCount = $newQuery->count();

            $expiringIds = CustomerSubscription::query()
                ->whereBetween('end_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->when($this->activityIds, fn ($q) => $q->whereHas('subscription', fn ($s) => $s->whereIn('activity_id', $this->activityIds)))
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

            $labels[]        = $cursor->format('M Y');
            $newSeries[]     = $newCount;
            $churnedSeries[] = $churned;
            $netSeries[]     = $newCount - $churned;

            $cursor->addMonth();
        }

        return [
            'chart' => [
                'type'       => 'bar',
                'height'     => 300,
                'toolbar'    => ['show' => false],
                'fontFamily' => 'Manrope, Segoe UI, Helvetica Neue, Arial, sans-serif',
                'foreColor'  => '#64748b',
            ],
            'series' => [
                ['name' => 'New Clients', 'data' => $newSeries],
                ['name' => 'Churned',     'data' => $churnedSeries],
                ['name' => 'Net Growth',  'data' => $netSeries],
            ],
            'colors'     => ['#10b981', '#f43f5e', '#6366f1'],
            'dataLabels' => ['enabled' => false],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 3,
                    'columnWidth'  => '60%',
                ],
            ],
            'xaxis' => [
                'categories' => $labels,
                'axisBorder' => ['show' => false],
                'axisTicks'  => ['show' => false],
            ],
            'yaxis' => [
                'labels' => ['style' => ['fontSize' => '12px']],
            ],
            'grid' => [
                'borderColor'     => '#e2e8f0',
                'strokeDashArray' => 4,
            ],
            'tooltip' => [
                'shared'    => true,
                'intersect' => false,
            ],
            'legend' => [
                'position'        => 'top',
                'horizontalAlign' => 'left',
                'fontSize'        => '12px',
            ],
        ];
    }
}
