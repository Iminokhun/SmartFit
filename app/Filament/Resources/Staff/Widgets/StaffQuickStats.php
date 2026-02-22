<?php

namespace App\Filament\Resources\Staff\Widgets;

use App\Models\Staff;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StaffQuickStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total staff', (string) Staff::query()->count())
                ->color('gray'),

            Stat::make('Active', (string) Staff::query()->where('status', 'active')->count())
                ->color('success'),

            Stat::make('Vacation', (string) Staff::query()->where('status', 'vacation')->count())
                ->color('warning'),

            Stat::make('Inactive', (string) Staff::query()->where('status', 'inactive')->count())
                ->color('danger'),
        ];
    }
}

