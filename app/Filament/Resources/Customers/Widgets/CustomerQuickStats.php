<?php

namespace App\Filament\Resources\Customers\Widgets;

use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerQuickStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total customers', (string) Customer::query()->count())
                ->color('gray'),

            Stat::make('Active', (string) Customer::query()->where('status', 'active')->count())
                ->color('success'),

            Stat::make('Inactive', (string) Customer::query()->where('status', 'inactive')->count())
                ->color('danger'),

            Stat::make('Blocked', (string) Customer::query()->where('status', 'blocked')->count())
                ->color('warning'),
        ];
    }
}

