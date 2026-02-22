<?php

namespace App\Filament\Widgets\Manager;

use App\Enums\InventoryItemType;
use App\Models\Inventory;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Visit;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ManagerOperationsStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = now()->toDateString();
        $weekday = strtolower(now()->format('l'));

        $todaySchedules = Schedule::query()
            ->whereJsonContains('days_of_week', $weekday)
            ->count();

        $todayVisits = Visit::query()
            ->whereDate('visited_at', $today)
            ->count();

        $todayVisited = Visit::query()
            ->whereDate('visited_at', $today)
            ->where('status', 'visited')
            ->count();

        $todayCollections = Payment::query()
            ->whereDate('created_at', $today)
            ->whereIn('status', ['paid', 'partial'])
            ->sum('amount');

        $lowStockCount = Inventory::query()
            ->where('item_type', '!=', InventoryItemType::Asset->value)
            ->where('quantity', '<=', 10)
            ->count();

        return [
            Stat::make('Today schedules', (string) $todaySchedules)
                ->description('Planned sessions for today')
                ->color('primary'),

            Stat::make('Attendance today', (string) $todayVisits)
                ->description("Visited: {$todayVisited}")
                ->color('success'),

            Stat::make('Payments today', number_format((float) $todayCollections, 2, '.', ','))
                ->description('Paid + Partial collections')
                ->color('warning'),

            Stat::make('Low stock items', (string) $lowStockCount)
                ->description('Quantity <= 10')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}

