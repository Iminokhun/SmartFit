<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class OverviewInventoryExpenseCategoryPieChart extends ApexChartWidget
{
    protected static ?string $chartId = 'overviewInventoryExpenseCategoryPieChart';
    protected static ?string $heading = null;

    public ?string $from = null;
    public ?string $until = null;

    protected function getOptions(): array
    {
        [$from, $until] = $this->resolveDateRange();

        $categoryIds = ExpenseCategory::query()
            ->get(['id', 'name'])
            ->filter(function (ExpenseCategory $category) {
                $name = str($category->name)->lower()->replace(['_', '-'], ' ')->squish()->value();

                return in_array($name, ['asset', 'assets', 'consumable', 'consumables', 'retail', 'retails'], true);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $rows = Expense::query()
            ->selectRaw('expense_categories.name as category_name, SUM(expenses.amount) as total')
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.category_id')
            ->whereBetween('expenses.expenses_date', [$from->toDateString(), $until->toDateString()])
            ->whereIn('expenses.category_id', $categoryIds)
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->get();

        if ($rows->isEmpty()) {
            return [
                'chart' => [
                    'type' => 'donut',
                    'height' => 280,
                    'fontFamily' => 'Manrope, Segoe UI, Helvetica Neue, Arial, sans-serif',
                    'foreColor' => '#64748b',
                ],
                'series' => [1],
                'labels' => ['No inventory expense data'],
                'colors' => ['#cbd5e1'],
                'legend' => ['position' => 'bottom', 'fontSize' => '12px'],
                'dataLabels' => ['enabled' => false],
            ];
        }

        return [
            'chart' => [
                'type' => 'donut',
                'height' => 280,
                'fontFamily' => 'Manrope, Segoe UI, Helvetica Neue, Arial, sans-serif',
                'foreColor' => '#64748b',
            ],
            'series' => $rows->pluck('total')->map(fn ($value) => (float) $value)->all(),
            'labels' => $rows->pluck('category_name')->all(),
            'colors' => ['#334155', '#0ea5e9', '#f59e0b'],
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

