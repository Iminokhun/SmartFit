<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class FinanceExpenseCategoryPieChart extends ApexChartWidget
{
    protected static ?string $chartId = 'financeExpenseCategoryPieChart';
    protected static ?string $heading = null;

    public ?string $from = null;
    public ?string $until = null;
    public ?int $expenseCategoryId = null;

    protected function getOptions(): array
    {
        [$from, $until] = $this->resolveDateRange();

        $rows = Expense::query()
            ->selectRaw('expense_categories.name as category_name, SUM(expenses.amount) as total')
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.category_id')
            ->whereBetween('expenses.expenses_date', [$from->toDateString(), $until->toDateString()])
            ->when($this->expenseCategoryId, fn (Builder $query) => $query->where('expenses.category_id', $this->expenseCategoryId))
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'chart' => [
                'type' => 'donut',
                'height' => 320,
                'fontFamily' => 'Manrope, Segoe UI, Helvetica Neue, Arial, sans-serif',
                'foreColor' => '#64748b',
            ],
            'series' => $rows->pluck('total')->map(fn ($value) => (float) $value)->all(),
            'labels' => $rows->pluck('category_name')->all(),
            'colors' => ['#2563eb', '#0f766e', '#f59e0b', '#7c3aed', '#e11d48', '#0ea5e9', '#f97316', '#334155'],
            'legend' => ['position' => 'bottom', 'fontSize' => '12px'],
            'dataLabels' => ['enabled' => false],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
{
    tooltip: {
        y: {
            formatter: function (value) {
                return Number(value).toLocaleString('en-US') + ' UZS';
            }
        }
    },
    plotOptions: {
        pie: {
            donut: {
                labels: {
                    show: true,
                    value: {
                        formatter: function (value) {
                            return Number(value).toLocaleString('en-US');
                        }
                    }
                }
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
