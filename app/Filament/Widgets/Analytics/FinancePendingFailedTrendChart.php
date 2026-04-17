<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class FinancePendingFailedTrendChart extends ApexChartWidget
{
    protected static ?string $chartId = 'financePendingFailedTrendChart';
    protected static ?string $heading = null;

    public ?string $from = null;
    public ?string $until = null;
    public array $activityId = [];
    public array $paymentMethod = [];

    protected function getOptions(): array
    {
        [$from, $until] = $this->resolveDateRange();

        $rows = Payment::query()
            ->selectRaw('DATE(payments.created_at) as date, payments.status, COUNT(*) as total')
            ->join('customer_subscriptions', 'customer_subscriptions.id', '=', 'payments.customer_subscription_id')
            ->join('subscriptions', 'subscriptions.id', '=', 'customer_subscriptions.subscription_id')
            ->whereBetween('payments.created_at', [$from, $until])
            ->whereIn('payments.status', ['pending', 'failed'])
            ->when($this->paymentMethod, fn (Builder $query) => $query->whereIn('payments.method', $this->paymentMethod))
            ->when($this->activityId, fn (Builder $query) => $query->whereIn('subscriptions.activity_id', $this->activityId))
            ->groupBy(DB::raw('DATE(payments.created_at)'), 'payments.status')
            ->orderBy('date')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->date][$row->status] = (int) $row->total;
        }

        $labels = [];
        $pendingSeries = [];
        $failedSeries = [];

        $cursor = $from->copy();
        while ($cursor->lte($until)) {
            $date = $cursor->toDateString();
            $labels[] = $cursor->format('M d');
            $pendingSeries[] = $map[$date]['pending'] ?? 0;
            $failedSeries[] = $map[$date]['failed'] ?? 0;
            $cursor->addDay();
        }

        return [
            'chart' => [
                'type' => 'line',
                'height' => 300,
                'toolbar' => ['show' => false],
                'fontFamily' => 'Manrope, Segoe UI, Helvetica Neue, Arial, sans-serif',
                'foreColor' => '#64748b',
            ],
            'series' => [
                ['name' => 'Pending', 'data' => $pendingSeries],
                ['name' => 'Failed', 'data' => $failedSeries],
            ],
            'colors' => ['#f59e0b', '#dc2626'],
            'dataLabels' => ['enabled' => false],
            'stroke' => ['curve' => 'smooth', 'width' => 3],
            'grid' => [
                'borderColor' => '#e2e8f0',
                'strokeDashArray' => 4,
            ],
            'xaxis' => [
                'categories' => $labels,
                'labels' => [
                    'rotate' => -45,
                    'hideOverlappingLabels' => true,
                    'showDuplicates' => false,
                    'trim' => true,
                ],
                'tickAmount' => min(30, count($labels)),
                'axisBorder' => ['show' => false],
                'axisTicks' => ['show' => false],
            ],
            'yaxis' => [
                'min' => 0,
                'forceNiceScale' => false,
                'tickAmount' => 4,
            ],
            'tooltip' => ['shared' => true, 'intersect' => false],
            'legend' => [
                'position' => 'top',
                'horizontalAlign' => 'left',
                'fontSize' => '12px',
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
{
    yaxis: {
        labels: {
            formatter: function (value) {
                return Math.round(value);
            }
        }
    },
    tooltip: {
        y: {
            formatter: function (value) {
                return Math.round(value) + ' payments';
            }
        }
    }
}
JS);
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
