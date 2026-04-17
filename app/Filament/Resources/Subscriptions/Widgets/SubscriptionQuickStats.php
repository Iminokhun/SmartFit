<?php

namespace App\Filament\Resources\Subscriptions\Widgets;

use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SubscriptionQuickStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total subscriptions', (string) Subscription::query()->count())
                ->color('gray'),

            Stat::make('With discount', (string) Subscription::query()->where('discount', '>', 0)->count())
                ->color('success'),

            Stat::make('Unlimited visits', (string) Subscription::query()->whereNull('visits_limit')->count())
                ->color('info'),

            Stat::make('With trainer', (string) Subscription::query()->whereNotNull('trainer_id')->count())
                ->color('primary'),
        ];
    }
}
