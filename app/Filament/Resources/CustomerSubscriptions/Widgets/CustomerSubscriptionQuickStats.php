<?php

namespace App\Filament\Resources\CustomerSubscriptions\Widgets;

use App\Models\CustomerSubscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerSubscriptionQuickStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total subscriptions', (string) CustomerSubscription::query()->count())
                ->color('gray'),

            Stat::make('Active', (string) CustomerSubscription::query()->where('status', 'active')->count())
                ->color('success'),

            Stat::make('Expired', (string) CustomerSubscription::query()->where('status', 'expired')->count())
                ->color('danger'),

            Stat::make('Open debt', number_format((float) CustomerSubscription::query()->sum('debt'), 2, '.', ','))
                ->color('warning'),
        ];
    }
}

