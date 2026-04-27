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
            'colors' => ['#1c2433', '#29a37a', '#c7cbd1'],
            'legend' => ['position' => 'bottom', 'fontSize' => '12px'],
            'dataLabels' => ['enabled' => false],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
{
    plotOptions: {
        pie: {
            donut: {
                size: '75%',
                labels: {
                    show: true,
                    name:  { show: true, fontWeight: 600, color: '#6b7280' },
                    value: {
                        show: true,
                        fontFamily: 'Fraunces',
                        fontSize: '24px',
                        fontWeight: 600,
                        formatter: function(val) { return Number(val).toLocaleString('en-US'); }
                    },
                    total: {
                        show: true,
                        showAlways: true,
                        label: 'TOTAL',
                        formatter: function(w) {
                            var sum = w.globals.seriesTotals.reduce(function(a, b) { return a + b; }, 0);
                            return Math.round(sum).toLocaleString('en-US');
                        }
                    }
                }
            }
        }
    },
    tooltip: { y: { formatter: function(val) { return Number(val).toLocaleString('en-US') + ' UZS'; } } },
    dataLabels: { enabled: false }
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

