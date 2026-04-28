<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class TrainerPersonal extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedUserCircle;
    protected static string|null|\UnitEnum $navigationGroup = 'Account';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Profile';
    protected static ?string $title = 'Trainer Profile';
    protected static ?string $slug = 'personal';

    protected string $view = 'filament.pages.trainer-personal';

    public static function shouldRegisterNavigation(): bool
    {
        return \Filament\Facades\Filament::getCurrentPanel()?->getId() !== 'admin';
    }

    public function getProfileSummaryProperty(): array
    {
        $user = auth()->user();

        return [
            'name' => (string) ($user?->name ?? '-'),
            'email' => (string) ($user?->email ?? '-'),
            'phone' => (string) ($user?->staff?->phone ?? '-'),
            'role' => ucfirst((string) ($user?->role?->name ?? 'trainer')),
            'specialization' => (string) ($user?->staff?->specialization ?? '-'),
        ];
    }

    public function getShiftRowsProperty(): array
    {
        $staff = auth()->user()?->staff;

        if (! $staff) {
            return [];
        }

        $allDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        return $staff->shifts()
            ->orderBy('start_time')
            ->get()
            ->map(fn ($shift) => [
                'days'     => array_map('strtolower', (array) $shift->days_of_week),
                'all_days' => $allDays,
                'from'     => \Carbon\Carbon::parse($shift->start_time)->format('H:i'),
                'to'       => \Carbon\Carbon::parse($shift->end_time)->format('H:i'),
            ])
            ->all();
    }

    public function getStatsProperty(): array
    {
        $staff = auth()->user()?->staff;

        $experienceYears = $staff?->experience_years ?? 0;

        $totalSessions = $staff
            ? \App\Models\ScheduleOccurrence::whereHas('schedule', fn ($q) => $q->where('trainer_id', $staff->id))->count()
            : 0;

        $visitsThisMonth = $staff
            ? \App\Models\Visit::where('trainer_id', $staff->id)
                ->where('status', 'visited')
                ->whereMonth('visited_at', now()->month)
                ->whereYear('visited_at', now()->year)
                ->count()
            : 0;

        $weekStart  = now()->startOfWeek()->toDateString();
        $weekEnd    = now()->endOfWeek()->toDateString();
        $weekVisits = $staff
            ? \App\Models\Visit::where('trainer_id', $staff->id)
                ->whereIn('status', ['visited', 'missed'])
                ->whereBetween(\Illuminate\Support\Facades\DB::raw('DATE(visited_at)'), [$weekStart, $weekEnd])
                ->selectRaw("SUM(CASE WHEN status='visited' THEN 1 ELSE 0 END) as visited, COUNT(*) as total")
                ->first()
            : null;
        $fillRate = ($weekVisits && $weekVisits->total > 0)
            ? round($weekVisits->visited / $weekVisits->total * 100) . '%'
            : '—';

        return [
            ['label' => 'Experience',        'value' => $experienceYears, 'suffix' => 'yrs'],
            ['label' => 'Total Sessions',    'value' => $totalSessions,   'suffix' => ''],
            ['label' => 'Visits This Month', 'value' => $visitsThisMonth, 'suffix' => ''],
            ['label' => 'Fill Rate (week)',  'value' => $fillRate,        'suffix' => ''],
        ];
    }

    public function getWeekScheduleRowsProperty(): array
    {
        $staff = auth()->user()?->staff;
        if (! $staff) {
            return [];
        }

        $days  = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $today = strtolower(now()->format('l'));

        $schedules = $staff->schedules()
            ->with(['activity:id,name', 'hall:id,name'])
            ->orderBy('start_time')
            ->get();

        $rows = [];
        foreach ($days as $day) {
            foreach ($schedules as $schedule) {
                if (in_array($day, (array) $schedule->days_of_week)) {
                    $rows[] = [
                        'day'      => ucfirst($day),
                        'activity' => $schedule->activity?->name ?? '-',
                        'hall'     => $schedule->hall?->name ?? '-',
                        'time'     => sprintf(
                            '%s - %s',
                            \Carbon\Carbon::parse($schedule->start_time)->format('H:i'),
                            \Carbon\Carbon::parse($schedule->end_time)->format('H:i')
                        ),
                        'is_today' => $day === $today,
                    ];
                }
            }
        }

        return $rows;
    }

    public function getTodayScheduleRowsProperty(): array
    {
        $staff = auth()->user()?->staff;

        if (! $staff) {
            return [];
        }

        $today = strtolower(now()->format('l'));

        return $staff->schedules()
            ->with(['activity:id,name', 'hall:id,name'])
            ->whereJsonContains('days_of_week', $today)
            ->orderBy('start_time')
            ->get()
            ->map(fn ($schedule) => [
                'activity' => $schedule->activity?->name ?? '-',
                'hall' => $schedule->hall?->name ?? '-',
                'time' => sprintf(
                    '%s - %s',
                    \Carbon\Carbon::parse($schedule->start_time)->format('H:i'),
                    \Carbon\Carbon::parse($schedule->end_time)->format('H:i')
                ),
            ])
            ->all();
    }
}

