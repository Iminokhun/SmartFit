<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\CustomerSubscription;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ForecastCollectionTrendChart extends ApexChartWidget
{
    protected static ?string $chartId = 'forecastCollectionTrendChart';
    protected static ?string $heading = null;

    public array $activityIds = [];

    protected function getOptions(): array
    {
        $today  = Carbon::today();
        $labels = [];
        $rates  = [];
        $collected  = [];
        $contracted = [];

        // Last 6 months including current
        for ($i = -5; $i <= 0; $i++) {
            $monthStart = $today->copy()->addMonths($i)->startOfMonth();
            $monthEnd   = $monthStart->copy()->endOfMonth();

            // Subscriptions starting this month
            $con = (float) CustomerSubscription::query()
                ->whereIn('status', ['active', 'pending'])
                ->whereYear('start_date', $monthStart->year)
                ->whereMonth('start_date', $monthStart->month)
                ->when($this->activityIds, fn (Builder $q) => $q->whereHas('subscription', fn (Builder $s) => $s->whereIn('activity_id', $this->activityIds)))
                ->sum('agreed_price');

            // Payments for those same subscriptions (regardless of payment date)
            $col = (float) Payment::query()
                ->whereIn('status', ['paid', 'partial'])
                ->whereHas('customerSubscription', fn (Builder $q) => $q
                    ->whereYear('start_date', $monthStart->year)
                    ->whereMonth('start_date', $monthStart->month)
                )
                ->when($this->activityIds, fn (Builder $q) => $q->whereHas('customerSubscription.subscription', fn (Builder $s) => $s->whereIn('activity_id', $this->activityIds)))
                ->sum('amount');

            $rate = $con > 0 ? min(round($col / $con * 100, 1), 100) : 0.0;

            $labels[]     = $monthStart->format('M Y');
            $rates[]      = $rate;
            $collected[]  = round($col / 1_000_000, 2);
            $contracted[] = round($con / 1_000_000, 2);
        }

        return [
            'chart' => [
                'type'       => 'line',
                'height'     => 300,
                'toolbar'    => ['show' => false],
                'fontFamily' => 'Manrope, Segoe UI, Helvetica Neue, Arial, sans-serif',
                'foreColor'  => '#64748b',
            ],
            'series' => [
                [
                    'name' => 'Collection Rate %',
                    'type' => 'line',
                    'data' => $rates,
                ],
                [
                    'name' => 'Collected (M UZS)',
                    'type' => 'bar',
                    'data' => $collected,
                ],
                [
                    'name' => 'Contracted (M UZS)',
                    'type' => 'bar',
                    'data' => $contracted,
                ],
            ],
            'colors'     => ['#6366f1', '#1c2433', '#c7cbd1'],
            'dataLabels' => ['enabled' => false],
            'stroke'     => ['width' => [3, 0, 0], 'curve' => 'smooth'],
            'markers'    => ['size' => [5, 0, 0], 'strokeWidth' => 0],
            'plotOptions' => [
                'bar' => ['borderRadius' => 3, 'columnWidth' => '50%'],
            ],
            'xaxis' => [
                'categories' => $labels,
                'axisBorder' => ['show' => false],
                'axisTicks'  => ['show' => false],
            ],
            'grid' => [
                'borderColor'     => '#f1f5f9',
                'strokeDashArray' => 4,
            ],
            'legend' => [
                'position'        => 'top',
                'horizontalAlign' => 'left',
                'fontSize'        => '12px',
            ],
            'annotations' => [
                'yaxis' => [
                    [
                        'y'               => 80,
                        'borderColor'     => '#10b981',
                        'borderWidth'     => 1,
                        'strokeDashArray' => 4,
                        'label'           => [
                            'text'  => '80% target',
                            'style' => ['fontSize' => '10px', 'color' => '#10b981', 'background' => 'transparent'],
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
    yaxis: [
        {
            seriesName: 'Collection Rate %',
            min: 0,
            max: 100,
            labels: {
                formatter: function(val) { return val.toFixed(0) + '%'; }
            }
        },
        {
            seriesName: 'Collected (M UZS)',
            opposite: true,
            labels: {
                formatter: function(val) { return val.toFixed(1) + ' M'; }
            }
        },
        {
            seriesName: 'Contracted (M UZS)',
            show: false
        }
    ],
    tooltip: {
        shared: true,
        intersect: false,
        y: [
            { formatter: function(val) { return val.toFixed(1) + '%'; } },
            { formatter: function(val) { return (val * 1000000).toLocaleString('en-US') + ' UZS'; } },
            { formatter: function(val) { return (val * 1000000).toLocaleString('en-US') + ' UZS'; } }
        ]
    }
}
JS);
    }
}
