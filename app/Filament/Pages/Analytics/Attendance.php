<?php

namespace App\Filament\Pages\Analytics;

use App\Models\Activity;
use App\Models\Hall;
use App\Models\ScheduleOccurrence;
use App\Models\Staff;
use App\Models\Visit;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Attendance extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedCalendarDays;
    protected static string|null|\UnitEnum $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Attendance Analytics';
    protected static ?string $navigationLabel = 'Attendance';
    protected static ?string $slug = 'analytics/attendance';

    protected string $view = 'filament.pages.analytics.attendance';

    public string $period = 'month';
    public ?string $from = null;
    public ?string $until = null;
    public ?int $trainerId = null;
    public ?int $hallId = null;
    public ?int $activityId = null;
    public ?string $status = null;
    public ?string $dayOfWeek = null;

    public function mount(): void
    {
        $this->syncPeriodDates();
    }

    public function updatedPeriod(): void
    {
        $this->syncPeriodDates();
    }

    public function resetFilters(): void
    {
        $this->period = 'month';
        $this->trainerId = null;
        $this->hallId = null;
        $this->activityId = null;
        $this->status = null;
        $this->dayOfWeek = null;
        $this->syncPeriodDates();
    }

    protected function getViewData(): array
    {
        [$from, $until] = $this->resolveDateRange();

        $visitsBase = $this->baseVisitsQuery($from, $until);

        $totalVisits = (clone $visitsBase)->count();
        $visitedCount = (clone $visitsBase)->where('visits.status', 'visited')->count();
        $missedCount = (clone $visitsBase)->where('visits.status', 'missed')->count();
        $cancelledCount = (clone $visitsBase)->where('visits.status', 'cancelled')->count();
        $noShowCount = $missedCount;

        $attendanceRate = $totalVisits > 0 ? ($visitedCount / $totalVisits) * 100 : 0;
        $missedRate = $totalVisits > 0 ? ($missedCount / $totalVisits) * 100 : 0;
        $cancelledRate = $totalVisits > 0 ? ($cancelledCount / $totalVisits) * 100 : 0;

        $trainerPerformance = ScheduleOccurrence::query()
            ->selectRaw("staff.id as trainer_id, staff.full_name as trainer_name, COUNT(DISTINCT schedule_occurrences.id) as sessions_count, SUM(COALESCE(schedule_occurrences.max_participants, schedules.max_participants, 0)) as total_capacity, SUM(CASE WHEN visits.status = 'visited' THEN 1 ELSE 0 END) as total_visited")
            ->join('schedules', 'schedules.id', '=', 'schedule_occurrences.schedule_id')
            ->join('staff', 'staff.id', '=', 'schedules.trainer_id')
            ->leftJoin('visits', function ($join) {
                $join->on('visits.occurrence_id', '=', 'schedule_occurrences.id');
            })
            ->whereBetween('schedule_occurrences.date', [$from->toDateString(), $until->toDateString()])
            ->when($this->trainerId, fn (Builder $query) => $query->where('schedules.trainer_id', $this->trainerId))
            ->when($this->hallId, fn (Builder $query) => $query->where('schedules.hall_id', $this->hallId))
            ->when($this->activityId, fn (Builder $query) => $query->where('schedules.activity_id', $this->activityId));

        if ($this->dayOfWeek) {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'pgsql') {
                $trainerPerformance->whereRaw("LOWER(TO_CHAR(schedule_occurrences.date, 'FMDay')) = ?", [strtolower($this->dayOfWeek)]);
            } else {
                $trainerPerformance->whereRaw('LOWER(DAYNAME(schedule_occurrences.date)) = ?', [strtolower($this->dayOfWeek)]);
            }
        }

        $trainerPerformance = $trainerPerformance
            ->groupBy('staff.id', 'staff.full_name')
            ->get()
            ->map(function ($row) {
                $capacity = (int) $row->total_capacity;
                $visited = (int) $row->total_visited;
                $sessions = (int) $row->sessions_count;

                return (object) [
                    'trainer_id' => (int) $row->trainer_id,
                    'trainer_name' => $row->trainer_name,
                    'sessions_count' => $sessions,
                    'total_visited' => $visited,
                    'total_capacity' => $capacity,
                    'fill_rate' => $capacity > 0 ? round(($visited / $capacity) * 100, 1) : 0.0,
                    'avg_participants' => $sessions > 0 ? round($visited / $sessions, 1) : 0.0,
                ];
            })
            ->filter(fn ($row) => $row->sessions_count > 0)
            ->values();

        $topTrainers = $trainerPerformance
            ->sortByDesc(fn ($row) => $row->fill_rate)
            ->take(5)
            ->values();

        $lowestTrainers = $trainerPerformance
            ->sortBy(fn ($row) => $row->fill_rate)
            ->take(5)
            ->values();

        $hasData = $totalVisits > 0 || $trainerPerformance->isNotEmpty();

        return [
            'periodOptions' => [
                'today' => 'Today',
                'week' => 'This week',
                'month' => 'This month',
                'range' => 'Custom range',
            ],
            'statusOptions' => [
                'visited' => 'Visited',
                'missed' => 'Missed',
                'cancelled' => 'Cancelled',
            ],
            'dayOptions' => [
                'monday' => 'Monday',
                'tuesday' => 'Tuesday',
                'wednesday' => 'Wednesday',
                'thursday' => 'Thursday',
                'friday' => 'Friday',
                'saturday' => 'Saturday',
                'sunday' => 'Sunday',
            ],
            'trainers' => Staff::query()->orderBy('full_name')->pluck('full_name', 'id')->all(),
            'halls' => Hall::query()->orderBy('name')->pluck('name', 'id')->all(),
            'activities' => Activity::query()->orderBy('name')->pluck('name', 'id')->all(),
            'metrics' => [
                'totalVisits' => $totalVisits,
                'attendanceRate' => round($attendanceRate, 1),
                'missedRate' => round($missedRate, 1),
                'cancelledRate' => round($cancelledRate, 1),
                'noShowCount' => $noShowCount,
            ],
            'topTrainers' => $topTrainers,
            'lowestTrainers' => $lowestTrainers,
            'hasData' => $hasData,
            'rangeLabel' => $from->toDateString() . ' -> ' . $until->toDateString(),
        ];
    }

    private function baseVisitsQuery(Carbon $from, Carbon $until): Builder
    {
        $query = Visit::query()
            ->join('schedules', 'schedules.id', '=', 'visits.schedule_id')
            ->leftJoin('schedule_occurrences', 'schedule_occurrences.id', '=', 'visits.occurrence_id')
            ->whereBetween(
                DB::raw('COALESCE(schedule_occurrences.date, DATE(visits.visited_at))'),
                [$from->toDateString(), $until->toDateString()]
            )
            ->when($this->trainerId, fn (Builder $q) => $q->where('visits.trainer_id', $this->trainerId))
            ->when($this->hallId, fn (Builder $q) => $q->where('schedules.hall_id', $this->hallId))
            ->when($this->activityId, fn (Builder $q) => $q->where('schedules.activity_id', $this->activityId))
            ->when($this->status, fn (Builder $q) => $q->where('visits.status', $this->status));

        if ($this->dayOfWeek) {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'pgsql') {
                $query->whereRaw(
                    "LOWER(TO_CHAR(COALESCE(schedule_occurrences.date, DATE(visits.visited_at)), 'FMDay')) = ?",
                    [strtolower($this->dayOfWeek)]
                );
            } else {
                $query->whereRaw(
                    'LOWER(DAYNAME(COALESCE(schedule_occurrences.date, DATE(visits.visited_at)))) = ?',
                    [strtolower($this->dayOfWeek)]
                );
            }
        }

        return $query;
    }

    private function resolveDateRange(): array
    {
        $from = $this->from ? Carbon::parse($this->from) : Carbon::today();
        $until = $this->until ? Carbon::parse($this->until) : Carbon::today();

        if ($from->gt($until)) {
            [$from, $until] = [$until, $from];
        }

        return [$from->startOfDay(), $until->endOfDay()];
    }

    private function syncPeriodDates(): void
    {
        $today = Carbon::today();

        switch ($this->period) {
            case 'today':
                $this->from = $today->toDateString();
                $this->until = $today->toDateString();
                break;
            case 'week':
                $this->from = $today->copy()->startOfWeek()->toDateString();
                $this->until = $today->copy()->endOfWeek()->toDateString();
                break;
            case 'range':
                $this->from ??= $today->toDateString();
                $this->until ??= $today->toDateString();
                break;
            case 'month':
            default:
                $this->from = $today->copy()->startOfMonth()->toDateString();
                $this->until = $today->copy()->endOfMonth()->toDateString();
                break;
        }
    }
}
