<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Customer;
use App\Models\CustomerSubscription;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ClientsNewActiveChart extends ApexChartWidget
{
    protected static ?string $chartId = 'clientsNewActiveChart';
    protected static ?string $heading = null;

    public ?string $from = null;
    public ?string $until = null;
    public ?int $activityId = null;

    protected function getOptions(): array
    {
        [$from, $until] = $this->resolveDateRange();

        $newRows = Customer::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->whereBetween('created_at', [$from, $until])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $newSeries = [];
        $activeSeries = [];

        $cursor = $from->copy();
        while ($cursor->lte($until)) {
            $date = $cursor->toDateString();
            $labels[] = $cursor->format('M d');
            $newSeries[] = (int) ($newRows[$date]->total ?? 0);

            $activeSeries[] = CustomerSubscription::query()
                ->where('status', 'active')
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->when($this->activityId, function (Builder $query) {
                    $query->whereHas('subscription', function (Builder $subQuery) {
                        $subQuery->where('activity_id', $this->activityId);
                    });
                })
                ->distinct('customer_id')
                ->count('customer_id');

            $cursor->addDay();
        }

        return [
            'chart' => [
                'type' => 'line',
                'height' => 280,
                'toolbar' => ['show' => false],
                'fontFamily' => 'Manrope, Segoe UI, Helvetica Neue, Arial, sans-serif',
                'foreColor' => '#64748b',
            ],
            'series' => [
                ['name' => 'New customers', 'data' => $newSeries],
                ['name' => 'Active customers', 'data' => $activeSeries],
            ],
            'colors' => ['#2563eb', '#0f766e'],
            'dataLabels' => ['enabled' => false],
            'stroke' => ['curve' => 'smooth', 'width' => 3],
            'grid' => [
                'borderColor' => '#e2e8f0',
                'strokeDashArray' => 4,
            ],
            'xaxis' => [
                'categories' => $labels,
                'labels' => ['rotate' => -45],
                'axisBorder' => ['show' => false],
                'axisTicks' => ['show' => false],
            ],
            'tooltip' => ['shared' => true, 'intersect' => false],
            'legend' => [
                'position' => 'top',
                'horizontalAlign' => 'left',
                'fontSize' => '12px',
            ],
        ];
    }

    private function resolveDateRange(): array
    {
        $from = $this->from ? Carbon::parse($this->from) : Carbon::today();
        $until = $this->until ? Carbon::parse($this->until) : Carbon::today();

        if ($from->gt($until)) {
            [$from, $until] = [$until, $from];
        }

        return [$from->startOfDay(), $until->endOfDay()];
    }
}

