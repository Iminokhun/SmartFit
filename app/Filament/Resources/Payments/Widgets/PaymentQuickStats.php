<?php

namespace App\Filament\Resources\Payments\Widgets;

use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PaymentQuickStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total payments', (string) Payment::query()->count())
                ->color('gray'),

            Stat::make('Paid', (string) Payment::query()->where('status', 'paid')->count())
                ->color('success'),

            Stat::make('Pending', (string) Payment::query()->where('status', 'pending')->count())
                ->color('warning'),

            Stat::make('Failed', (string) Payment::query()->where('status', 'failed')->count())
                ->color('danger'),
        ];
    }
}
