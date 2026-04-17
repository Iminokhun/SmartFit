<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Expense;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class OverviewCollectionsMonthlyChart extends ApexChartWidget
{
    protected static ?string $chartId = 'overviewCollectionsMonthlyChart';
    protected static ?string $heading = null;

    public ?string $from = null;
    public ?string $until = null;

    protected function getOptions(): array
    {
        [$from, $until] = $this->resolveDateRange();

        $labels = [];
        $revenueSeries = [];
        $profitSeries  = [];

        $cursor = $from->copy()->startOfMonth();
        $end    = $until->copy()->endOfMonth();

        while ($cursor->lte($end)) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd   = $cursor->copy()->endOfMonth();
            $labels[]   = $cursor->format('M');

            $revenue = (float) Payment::query()
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->whereIn('status', ['paid', 'partial'])
                ->sum('amount');

            $expenses = (float) Expense::query()
                ->whereBetween('expenses_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum('amount');

            $revenueSeries[] = $revenue;
            $profitSeries[]  = round($revenue - $expenses, 2);

            $cursor->addMonth();
        }

        return [
            'chart'       => ['type' => 'bar', 'height' => 280, 'toolbar' => ['show' => false], 'fontFamily' => 'Manrope, sans-serif'],
            'series'      => [
                ['name' => 'Revenue',    'data' => $revenueSeries],
                ['name' => 'Net Profit', 'data' => $profitSeries],
            ],
            'colors'      => ['#c7cbd1', '#1c2433'],
            'plotOptions' => ['bar' => ['columnWidth' => '55%', 'borderRadius' => 3]],
            'dataLabels'  => ['enabled' => false],
            'xaxis'       => ['categories' => $labels, 'axisBorder' => ['show' => false], 'axisTicks' => ['show' => false]],
            'legend'      => ['position' => 'top', 'horizontalAlign' => 'right', 'fontSize' => '12px'],
            'grid'        => ['borderColor' => '#f1f5f9', 'strokeDashArray' => 4, 'yaxis' => ['lines' => ['show' => true]], 'xaxis' => ['lines' => ['show' => false]]],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
{
    yaxis: {
        labels: {
            formatter: function (value) {
                if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                if (value >= 1000) return (value / 1000).toFixed(0) + 'K';
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

    private function resolveDateRange(): array
    {
        return [Carbon::today()->startOfYear()->startOfDay(), Carbon::today()->endOfDay()];
    }
}
