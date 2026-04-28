<?php

namespace App\Services\Telegram\MiniApp;

use App\Models\CustomerSubscription;
use App\Models\Schedule;
use Carbon\Carbon;

class TelegramScheduleService
{
    public function scheduleSummary(int $customerId): array
    {
        $activityIds = CustomerSubscription::query()
            ->where('customer_id', $customerId)
            ->where('status', 'active')
            ->whereHas('subscription')
            ->with('subscription:id,activity_id')
            ->get()
            ->pluck('subscription.activity_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($activityIds)) {
            return ['has_items' => false, 'items' => []];
        }

        $schedules = Schedule::query()
            ->with(['activity:id,name', 'hall:id,name', 'staff:id,full_name'])
            ->whereIn('activity_id', $activityIds)
            ->get();

        if ($schedules->isEmpty()) {
            return ['has_items' => false, 'items' => []];
        }

        $todayKey = strtolower(now()->format('l'));
        $todayItems = $schedules
            ->filter(fn (Schedule $schedule) => in_array($todayKey, $this->normalizedDays($schedule), true))
            ->sortBy('start_time')
            ->take(5)
            ->values();

        if ($todayItems->isNotEmpty()) {
            $nowTime = now()->format('H:i:s');
            $nextFound = false;
            $items = $todayItems->map(function (Schedule $schedule) use ($nowTime, &$nextFound) {
                $isPast = ((string) $schedule->end_time) < $nowTime;
                $isNext = ! $isPast && ! $nextFound;
                if ($isNext) {
                    $nextFound = true;
                }

                return $this->formatScheduleItem($schedule, null, true, $isPast, $isNext);
            })->all();

            return ['has_items' => true, 'items' => $items];
        }

        $upcoming = $schedules
            ->map(function (Schedule $schedule) {
                $next = $this->nextClassDayMeta($schedule);
                if (! $next) {
                    return null;
                }

                return [
                    'schedule' => $schedule,
                    'days_ahead' => $next['days_ahead'],
                    'day_label' => $next['day_label'],
                ];
            })
            ->filter()
            ->sortBy([
                ['days_ahead', 'asc'],
                [fn (array $row) => (string) $row['schedule']->start_time, 'asc'],
            ])
            ->take(5)
            ->values();

        if ($upcoming->isEmpty()) {
            return ['has_items' => false, 'items' => []];
        }

        $items = $upcoming->map(function (array $row) {
            return $this->formatScheduleItem($row['schedule'], $row['day_label'], false);
        })->all();

        return ['has_items' => true, 'items' => $items];
    }

    private function formatScheduleItem(Schedule $schedule, ?string $dayLabel, bool $isToday, bool $isPast = false, bool $isNext = false): array
    {
        return [
            'time_from' => Carbon::parse($schedule->start_time)->format('H:i'),
            'time_to' => Carbon::parse($schedule->end_time)->format('H:i'),
            'activity' => $schedule->activity?->name ?? 'Activity',
            'hall' => $schedule->hall?->name ?? null,
            'trainer' => $schedule->staff?->full_name ?? null,
            'day' => $isToday ? 'Today' : $dayLabel,
            'is_today' => $isToday,
            'is_past' => $isPast,
            'is_next' => $isNext,
        ];
    }

    private function normalizedDays(Schedule $schedule): array
    {
        $days = is_array($schedule->days_of_week) ? $schedule->days_of_week : [];

        return collect($days)
            ->map(fn ($day) => strtolower(trim((string) $day)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function nextClassDayMeta(Schedule $schedule): ?array
    {
        $dayToIndex = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 7,
        ];

        $currentIndex = (int) now()->dayOfWeekIso;
        $bestDelta = null;
        $bestDay = null;

        foreach ($this->normalizedDays($schedule) as $day) {
            $target = $dayToIndex[$day] ?? null;
            if (! $target) {
                continue;
            }

            $delta = ($target - $currentIndex + 7) % 7;
            if ($delta === 0) {
                $delta = 7;
            }

            if ($bestDelta === null || $delta < $bestDelta) {
                $bestDelta = $delta;
                $bestDay = ucfirst($day);
            }
        }

        if ($bestDelta === null || $bestDay === null) {
            return null;
        }

        return [
            'days_ahead' => $bestDelta,
            'day_label' => $bestDay,
        ];
    }
}
