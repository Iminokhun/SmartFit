<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Expense;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class FinanceRevenueExpensesTrendChart extends ApexChartWidget
{
    protected static ?string $chartId = 'financeRevenueExpensesTrendChart';
    protected static ?string $heading = null;

    public ?string $from = null;
    public ?string $until = null;
    public ?int $activityId = null;
    public ?string $paymentMethod = null;
    public ?string $paymentStatus = null;
    public ?int $expenseCategoryId = null;

    protected function getOptions(): array
    {
        [$from, $until] = $this->resolveDateRange();
        $revenueStatuses = $this->resolveRevenueStatuses();

        $revenueRows = Payment::query()
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

        $expenseRows = Expense::query()
            ->selectRaw('DATE(expenses.expenses_date) as date, SUM(expenses.amount) as total')
            ->whereBetween('expenses.expenses_date', [$from->toDateString(), $until->toDateString()])
            ->when($this->expenseCategoryId, fn (Builder $query) => $query->where('expenses.category_id', $this->expenseCategoryId))
            ->groupBy(DB::raw('DATE(expenses.expenses_date)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $revenueSeries = [];
        $expenseSeries = [];
        $cursor = $from->copy();
        while ($cursor->lte($until)) {
            $date = $cursor->toDateString();
            $labels[] = $cursor->format('M d');
            $revenueSeries[] = (float) ($revenueRows[$date]->total ?? 0);
            $expenseSeries[] = (float) ($expenseRows[$date]->total ?? 0);
            $cursor->addDay();
        }

        return [
            'chart' => [
                'type' => 'area',
                'height' => 300,
                'toolbar' => ['show' => false],
                'fontFamily' => 'Manrope, Segoe UI, Helvetica Neue, Arial, sans-serif',
                'foreColor' => '#64748b',
            ],
            'series' => [
                ['name' => 'Revenue', 'data' => $revenueSeries],
                ['name' => 'Expenses', 'data' => $expenseSeries],
            ],
            'colors' => ['#15803d', '#b91c1c'],
            'dataLabels' => ['enabled' => false],
            'stroke' => ['curve' => 'smooth', 'width' => 2],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shadeIntensity' => 1,
                    'opacityFrom' => 0.2,
                    'opacityTo' => 0.03,
                    'stops' => [0, 90, 100],
                ],
            ],
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
