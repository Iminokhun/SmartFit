<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\CustomerSubscription;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ForecastRevenueChart extends ApexChartWidget
{
    protected static ?string $chartId = 'forecastRevenueChart';
    protected static ?string $heading = null;

    public ?int $activityId = null;

    protected function getOptions(): array
    {
        $today = Carbon::today();

        $labels          = [];
        $actualSeries    = [];
        $forecastSeries  = [];

        // 3 past months + current + 2 future = 6 months total
        for ($i = -3; $i <= 2; $i++) {
            $monthStart = $today->copy()->addMonths($i)->startOfMonth();
            $monthEnd   = $monthStart->copy()->endOfMonth();
            $isFuture   = $monthStart->gt($today->copy()->endOfMonth());

            // Actual: only past and current month
            $actual = 0.0;
            if (! $isFuture) {
                $actual = (float) Payment::query()
                    ->whereIn('status', ['paid', 'partial'])
                    ->whereBetween('created_at', [$monthStart->copy()->startOfDay(), $monthEnd->copy()->endOfDay()])
                    ->when($this->activityId, fn (Builder $q) => $q->whereHas('customerSubscription.subscription', fn (Builder $s) => $s->where('activity_id', $this->activityId)))
                    ->sum('amount');
            }

            // Contracted: count agreed_price once, in the month subscription starts
            $contracted = (float) CustomerSubscription::query()
                ->whereIn('status', ['active', 'pending'])
                ->whereYear('start_date', $monthStart->year)
                ->whereMonth('start_date', $monthStart->month)
                ->when($this->activityId, fn (Builder $q) => $q->whereHas('subscription', fn (Builder $s) => $s->where('activity_id', $this->activityId)))
                ->sum('agreed_price');

            $labels[]         = $monthStart->format('M Y');
            $actualSeries[]   = round($actual / 1_000_000, 2);
            $forecastSeries[] = round($contracted / 1_000_000, 2);
        }

        return [
            'chart' => [
                'type'       => 'bar',
                'height'     => 320,
                'toolbar'    => ['show' => false],
                'fontFamily' => 'Manrope, Segoe UI, Helvetica Neue, Arial, sans-serif',
                'foreColor'  => '#64748b',
            ],
            'series' => [
                ['name' => 'Actual collected', 'data' => $actualSeries],
                ['name' => 'Contracted',        'data' => $forecastSeries],
            ],
            'colors'      => ['#3b82f6', '#f59e0b'],
            'dataLabels'  => ['enabled' => false],
            'plotOptions' => [
                'bar' => [
                    'borderRadius'  => 4,
                    'columnWidth'   => '55%',
                ],
            ],
            'xaxis' => [
                'categories' => $labels,
                'axisBorder' => ['show' => false],
                'axisTicks'  => ['show' => false],
            ],
            'yaxis' => ['labels' => []],
            'grid' => [
                'borderColor'     => '#f1f5f9',
                'strokeDashArray' => 4,
            ],
            'tooltip' => ['shared' => true, 'intersect' => false],
            'legend' => [
                'position'        => 'top',
                'horizontalAlign' => 'left',
                'fontSize'        => '12px',
            ],
            'annotations' => [
                'xaxis' => [
                    [
                        'x'           => $today->format('M Y'),
                        'borderColor' => '#94a3b8',
                        'borderWidth' => 1,
                        'strokeDashArray' => 4,
                        'label'       => [
                            'text'  => 'Today',
                            'style' => ['fontSize' => '10px', 'color' => '#64748b'],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
{
    yaxis: {
        labels: { formatter: function(val) { return val.toFixed(1) + ' M'; } }
    },
    tooltip: {
        y: { formatter: function(val) { return (val * 1000000).toLocaleString('en-US') + ' UZS'; } }
    }
}
JS);
    }
}
