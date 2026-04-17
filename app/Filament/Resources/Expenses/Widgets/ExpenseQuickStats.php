<?php

namespace App\Filament\Resources\Expenses\Widgets;

use App\Models\Expense;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExpenseQuickStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $thisMonth = Expense::query()
            ->whereYear('expenses_date', now()->year)
            ->whereMonth('expenses_date', now()->month)
            ->sum('amount');

        $thisYear = Expense::query()
            ->whereYear('expenses_date', now()->year)
            ->sum('amount');

        $total = Expense::query()->count();

        $avg = $total > 0
            ? Expense::query()->avg('amount')
            : 0;

        return [
            Stat::make('Total expenses', (string) $total)
                ->color('gray'),

            Stat::make('This month', number_format($thisMonth, 0, '.', ' ') . ' UZS')
                ->color('warning'),

            Stat::make('This year', number_format($thisYear, 0, '.', ' ') . ' UZS')
                ->color('danger'),

            Stat::make('Avg per expense', number_format($avg, 0, '.', ' ') . ' UZS')
                ->color('info'),
        ];
    }
}
