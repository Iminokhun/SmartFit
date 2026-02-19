<?php

namespace App\Filament\Widgets\Analytics;

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
        $series = [];

        $cursor = $from->copy()->startOfMonth();
        $end = $until->copy()->endOfMonth();

        while ($cursor->lte($end)) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();
            $labels[] = $cursor->format('M');

            $series[] = (float) Payment::query()
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->whereIn('status', ['paid', 'partial'])
                ->sum('amount');

            $cursor->addMonth();
        }

        return [
            'chart' => ['type' => 'bar', 'height' => 280, 'toolbar' => ['show' => false]],
            'series' => [
                ['name' => 'Collections', 'data' => $series],
            ],
            'colors' => ['#f59e0b'],
            'plotOptions' => ['bar' => ['columnWidth' => '45%', 'borderRadius' => 4]],
            'dataLabels' => ['enabled' => false],
            'xaxis' => ['categories' => $labels],
            'legend' => ['show' => false],
            'grid' => ['borderColor' => '#e5e7eb', 'strokeDashArray' => 3],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
{
    yaxis: {
        labels: {
            formatter: function (value) {
                return Number(value).toLocaleString('en-US');
            }
        }
    }
}
JS);
    }

    private function resolveDateRange(): array
    {
        $from = $this->from ? Carbon::parse($this->from) : Carbon::today()->startOfYear();
        $until = $this->until ? Carbon::parse($this->until) : Carbon::today();

        if ($from->gt($until)) {
            [$from, $until] = [$until, $from];
        }

        return [$from->startOfDay(), $until->endOfDay()];
    }
}
