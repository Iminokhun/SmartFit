<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\CustomerSubscription;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ForecastExpiringValueChart extends ApexChartWidget
{
    protected static ?string $chartId = 'forecastExpiringValueChart';
    protected static ?string $heading = null;

    public array $activityIds = [];

    protected function getOptions(): array
    {
        $today  = Carbon::today();
        $labels = [];
        $clean  = [];
        $atRisk = [];
        $counts = [];

        // Current month + next 3 = 4 months total
        for ($i = 0; $i <= 3; $i++) {
            $monthStart = $today->copy()->addMonths($i)->startOfMonth();
            $monthEnd   = $monthStart->copy()->endOfMonth();

            $base = CustomerSubscription::query()
                ->whereIn('status', ['active', 'pending'])
                ->whereDate('end_date', '>=', $monthStart->toDateString())
                ->whereDate('end_date', '<=', $monthEnd->toDateString())
                ->when($this->activityIds, fn (Builder $q) => $q->whereHas('subscription', fn (Builder $s) => $s->whereIn('activity_id', $this->activityIds)));

            $cleanVal  = round((float) (clone $base)->where('debt', '<=', 0)->sum('agreed_price') / 1_000_000, 2);
            $riskVal   = round((float) (clone $base)->where('debt', '>', 0)->sum('agreed_price') / 1_000_000, 2);
            $count     = (clone $base)->count();

            $labels[] = $monthStart->format('M Y');
            $clean[]  = $cleanVal;
            $atRisk[] = $riskVal;
            $counts[] = $count;
        }

        return [
            'chart' => [
                'type'       => 'bar',
                'height'     => 300,
                'stacked'    => true,
                'toolbar'    => ['show' => false],
                'fontFamily' => 'Manrope, Segoe UI, Helvetica Neue, Arial, sans-serif',
                'foreColor'  => '#64748b',
            ],
            'series' => [
                [
                    'name' => 'Clean (no debt)',
                    'data' => $clean,
                ],
                [
                    'name' => 'At Risk (has debt)',
                    'data' => $atRisk,
                ],
            ],
            'colors'      => ['#10b981', '#f43f5e'],
            'dataLabels'  => ['enabled' => false],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 4,
                    'columnWidth'  => '45%',
                    'dataLabels'   => ['total' => ['enabled' => true, 'style' => ['fontSize' => '11px', 'fontWeight' => 600, 'colors' => ['#374151']]]],
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
                            'text'  => 'Current',
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
        labels: {
            formatter: function(val) { return val.toFixed(1) + ' M'; }
        }
    },
    tooltip: {
        y: {
            formatter: function(val) { return (val * 1000000).toLocaleString('en-US') + ' UZS'; }
        }
    },
    plotOptions: {
        bar: {
            dataLabels: {
                total: {
                    formatter: function(val) { return val.toFixed(1) + ' M'; }
                }
            }
        }
    }
}
JS);
    }
}
