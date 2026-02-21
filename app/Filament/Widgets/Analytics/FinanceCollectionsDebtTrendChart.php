<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\CustomerSubscription;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class FinanceCollectionsDebtTrendChart extends ApexChartWidget
{
    protected static ?string $chartId = 'financeCollectionsDebtTrendChart';
    protected static ?string $heading = null;

    public ?string $from = null;
    public ?string $until = null;
    public ?int $activityId = null;
    public ?string $paymentMethod = null;
    public ?string $paymentStatus = null;

    protected function getOptions(): array
    {
        [$from, $until] = $this->resolveDateRange();
        $revenueStatuses = $this->resolveRevenueStatuses();

        $collectionRows = Payment::query()
            ->selectRaw('DATE(payments.created_at) as date, SUM(payments.amount) as total')
            ->join('customer_subscriptions', 'customer_subscriptions.id', '=', 'payments.customer_subscription_id')
            ->join('subscriptions', 'subscriptions.id', '=', 'customer_subscriptions.subscription_id')
            ->whereBetween('payments.created_at', [$from, $until])
            ->whereIn('payments.status', $revenueStatuses)
            ->when($this->paymentMethod, fn (Builder $query) => $query->where('payments.method', $this->paymentMethod))
            ->when($this->paymentStatus, fn (Builder $query) => $query->where('payments.status', $this->paymentStatus))
            ->when($this->activityId, fn (Builder $query) => $query->where('subscriptions.activity_id', $this->activityId))
            ->groupBy(DB::raw('DATE(payments.created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $collectionSeries = [];
        $debtSeries = [];
        $cursor = $from->copy();
        while ($cursor->lte($until)) {
            $date = $cursor->toDateString();
            $labels[] = $cursor->format('M d');
            $collectionSeries[] = (float) ($collectionRows[$date]->total ?? 0);

            $debtSeries[] = (float) CustomerSubscription::query()
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->when($this->activityId, function (Builder $query) {
                    $query->whereHas('subscription', function (Builder $subQuery) {
                        $subQuery->where('activity_id', $this->activityId);
                    });
                })
                ->sum('debt');

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
                ['name' => 'Collections', 'data' => $collectionSeries],
                ['name' => 'Debt', 'data' => $debtSeries],
            ],
            'colors' => ['#1d4ed8', '#dc2626'],
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
        $from = $this->from ? Carbon::parse($this->from) : Carbon::today();
        $until = $this->until ? Carbon::parse($this->until) : Carbon::today();

        if ($from->gt($until)) {
            [$from, $until] = [$until, $from];
        }

        return [$from->startOfDay(), $until->endOfDay()];
    }

    private function resolveRevenueStatuses(): array
    {
        $allowed = ['paid', 'partial'];
        if ($this->paymentStatus) {
            return array_values(array_intersect($allowed, [$this->paymentStatus]));
        }

        return $allowed;
    }
}
