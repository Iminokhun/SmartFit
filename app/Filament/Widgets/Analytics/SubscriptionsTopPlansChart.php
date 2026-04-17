<?php

namespace App\Filament\Widgets\Analytics;

use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SubscriptionsTopPlansChart extends ApexChartWidget
{
    protected static ?string $chartId = 'subscriptionsTopPlans';
    protected static ?string $heading = null;

    public array $chartNames = [];
    public array $chartRevenue = [];

    protected function getOptions(): array
    {
        $height = max(220, count($this->chartNames) * 44);

        return [
            'chart' => [
                'type' => 'bar',
                'height' => $height,
                'toolbar' => ['show' => false],
                'fontFamily' => 'Manrope, Segoe UI, Helvetica Neue, Arial, sans-serif',
                'foreColor' => '#64748b',
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => true,
                    'borderRadius' => 4,
                ],
            ],
            'series' => [
                ['name' => 'Revenue', 'data' => $this->chartRevenue],
            ],
            'xaxis' => [
                'categories' => $this->chartNames,
                'axisBorder' => ['show' => false],
                'axisTicks' => ['show' => false],
            ],
            'colors' => ['#2563eb'],
            'dataLabels' => ['enabled' => false],
            'grid' => [
                'borderColor' => '#e2e8f0',
                'strokeDashArray' => 4,
                'xaxis' => ['lines' => ['show' => true]],
                'yaxis' => ['lines' => ['show' => false]],
            ],
            'tooltip' => ['shared' => true, 'intersect' => false],
            'legend' => ['show' => false],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
{
    xaxis: {
        labels: {
            formatter: function (value) {
                return Number(value).toLocaleString('en-US');
            }
        }
    },
    tooltip: {
        y: {
            formatter: function (value) {
                return Number(value).toLocaleString('en-US') + ' UZS';
            }
        }
    }
}
JS);
    }
}
