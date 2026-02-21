<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Customer;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class OverviewTotalCustomersChart extends ApexChartWidget
{
    protected static ?string $chartId = 'overviewTotalCustomersChart';
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
            $monthEnd = $cursor->copy()->endOfMonth();
            $labels[] = $cursor->format('M');
            $series[] = (int) Customer::query()->whereDate('created_at', '<=', $monthEnd->toDateString())->count();
            $cursor->addMonth();
        }

        return [
            'chart' => ['type' => 'line', 'height' => 280, 'toolbar' => ['show' => false]],
            'series' => [
                ['name' => 'Total customers', 'data' => $series],
            ],
            'colors' => ['#f59e0b'],
            'stroke' => ['curve' => 'smooth', 'width' => 3],
            'markers' => ['size' => 3],
            'dataLabels' => ['enabled' => false],
            'xaxis' => ['categories' => $labels],
            'legend' => ['show' => false],
            'grid' => ['borderColor' => '#e5e7eb', 'strokeDashArray' => 3],
        ];
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
