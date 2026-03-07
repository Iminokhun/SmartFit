<?php

namespace App\Filament\Widgets\Trainer;

use App\Models\Schedule;
use App\Models\ScheduleOccurrence;
use App\Models\Visit;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TrainerTodayStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $staffId = auth()->user()?->staff?->id;

        if (! $staffId) {
            return [
                Stat::make('Today sessions', '0')->color('gray'),
                Stat::make('Attendance today', '0')->color('gray'),
                Stat::make('This week sessions', '0')->color('gray'),
                Stat::make('Fill rate (week)', '0%')->color('gray'),
            ];
        }

        $today = now()->toDateString();
        $todayDay = strtolower(now()->format('l'));
        $weekFrom = now()->startOfWeek()->toDateString();
        $weekUntil = now()->endOfWeek()->toDateString();

        $todaySessions = Schedule::query()
            ->where('trainer_id', $staffId)
            ->whereJsonContains('days_of_week', $todayDay)
            ->count();

        $visitedToday = Visit::query()
            ->where('trainer_id', $staffId)
            ->whereDate('visited_at', $today)
            ->where('status', 'visited')
            ->count();

        $missedToday = Visit::query()
            ->where('trainer_id', $staffId)
            ->whereDate('visited_at', $today)
            ->where('status', 'missed')
            ->count();

        $cancelledToday = Visit::query()
            ->where('trainer_id', $staffId)
            ->whereDate('visited_at', $today)
            ->where('status', 'cancelled')
            ->count();

        $weekSessions = ScheduleOccurrence::query()
            ->whereBetween('date', [$weekFrom, $weekUntil])
            ->whereHas('schedule', fn ($q) => $q->where('trainer_id', $staffId))
            ->count();

        $weekVisited = Visit::query()
            ->where('trainer_id', $staffId)
            ->where('status', 'visited')
            ->whereHas('occurrence', fn ($q) => $q->whereBetween('date', [$weekFrom, $weekUntil]))
            ->count();

        $weekCapacity = ScheduleOccurrence::query()
            ->whereBetween('date', [$weekFrom, $weekUntil])
            ->whereHas('schedule', fn ($q) => $q->where('trainer_id', $staffId))
            ->sum('max_participants');

        $weekFillRate = $weekCapacity > 0 ? round(($weekVisited / $weekCapacity) * 100, 1) : 0.0;

        return [
            Stat::make('Today sessions', (string) $todaySessions)
                ->description('Planned classes today')
                ->color('primary'),

            Stat::make('Attendance today', (string) $visitedToday)
                ->description("Missed: {$missedToday}, Cancelled: {$cancelledToday}")
                ->color('success'),

            Stat::make('This week sessions', (string) $weekSessions)
                ->description('By schedule occurrences')
                ->color('warning'),

            Stat::make('Fill rate (week)', "{$weekFillRate}%")
                ->description("Visited {$weekVisited} / Capacity {$weekCapacity}")
                ->color($weekFillRate >= 70 ? 'success' : ($weekFillRate >= 40 ? 'warning' : 'danger')),
        ];
    }
}

