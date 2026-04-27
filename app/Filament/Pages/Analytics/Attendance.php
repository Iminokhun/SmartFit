<?php

namespace App\Filament\Pages\Analytics;

use App\Models\Activity;
use App\Models\CustomerCheckin;
use App\Models\Hall;
use App\Models\ScheduleOccurrence;
use App\Models\Staff;
use App\Models\Visit;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Attendance extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedCalendarDays;
    protected static string|null|\UnitEnum $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Attendance Analytics';
    protected static ?string $navigationLabel = 'Attendance';
    protected static ?string $slug = 'analytics/attendance';

    protected string $view = 'filament.pages.analytics.attendance';

    public ?array $data = [];

    public function mount(): void
    {
        $today = Carbon::today();

        $this->form->fill([
            'period'     => 'month',
            'from'       => $today->copy()->startOfMonth()->toDateString(),
            'until'      => $today->copy()->endOfMonth()->toDateString(),
            'trainerId'  => [],
            'hallId'     => [],
            'activityId' => [],
            'status'     => [],
            'dayOfWeek'  => [],
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Grid::make(['default' => 1, 'md' => 2, 'lg' => 4])
                    ->schema([
                        Select::make('period')
                            ->label('Period')
                            ->options([
                                'today' => 'Today',
                                'week'  => 'This week',
                                'month' => 'This month',
                                'range' => 'Custom range',
                            ])
                            ->default('month')
                            ->live()
                            ->afterStateUpdated(fn () => $this->syncPeriodDates()),

                        DatePicker::make('from')
                            ->label('From')
                            ->live()
                            ->disabled(fn () => ($this->data['period'] ?? 'month') !== 'range'),

                        DatePicker::make('until')
                            ->label('Until')
                            ->live()
                            ->disabled(fn () => ($this->data['period'] ?? 'month') !== 'range'),

                        Select::make('trainerId')
                            ->label('Trainer')
                            ->options(fn () => Staff::query()
                                ->whereHas('role', fn ($q) => $q->whereRaw('LOWER(name) = ?', ['trainer']))
                                ->orderBy('full_name')
                                ->pluck('full_name', 'id')
                                ->all()
                            )
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->placeholder('All')
                            ->live(),

                        Select::make('hallId')
                            ->label('Hall')
                            ->options(fn () => Hall::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->placeholder('All')
                            ->live(),

                        Select::make('activityId')
                            ->label('Activity')
                            ->options(fn () => Activity::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->placeholder('All')
                            ->live(),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'visited'   => 'Visited',
                                'missed'    => 'Missed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->multiple()
                            ->placeholder('All')
                            ->live(),

                        Select::make('dayOfWeek')
                            ->label('Day of week')
                            ->options([
                                'monday'    => 'Monday',
                                'tuesday'   => 'Tuesday',
                                'wednesday' => 'Wednesday',
                                'thursday'  => 'Thursday',
                                'friday'    => 'Friday',
                                'saturday'  => 'Saturday',
                                'sunday'    => 'Sunday',
                            ])
                            ->multiple()
                            ->placeholder('All')
                            ->live(),
                    ]),
            ])
            ->statePath('data');
    }

    public function resetFilters(): void
    {
        $today = Carbon::today();

        $this->form->fill([
            'period'     => 'month',
            'from'       => $today->copy()->startOfMonth()->toDateString(),
            'until'      => $today->copy()->endOfMonth()->toDateString(),
            'trainerId'  => [],
            'hallId'     => [],
            'activityId' => [],
            'status'     => [],
            'dayOfWeek'  => [],
        ]);
    }

    protected function getViewData(): array
    {
        [$from, $until] = $this->resolveDateRange();

        $trainerId  = !empty($this->data['trainerId'])  ? array_map('intval', (array) $this->data['trainerId'])  : [];
        $hallId     = !empty($this->data['hallId'])     ? array_map('intval', (array) $this->data['hallId'])     : [];
        $activityId = !empty($this->data['activityId']) ? array_map('intval', (array) $this->data['activityId']) : [];
        $status     = !empty($this->data['status'])     ? (array) $this->data['status']                          : [];
        $dayOfWeek  = !empty($this->data['dayOfWeek'])  ? (array) $this->data['dayOfWeek']                       : [];

        $visitsBase = $this->baseVisitsQuery($from, $until);

        $totalVisits    = (clone $visitsBase)->count();
        $visitedCount   = (clone $visitsBase)->where('visits.status', 'visited')->count();
        $missedCount    = (clone $visitsBase)->where('visits.status', 'missed')->count();
        $cancelledCount = (clone $visitsBase)->where('visits.status', 'cancelled')->count();
        $noShowCount    = $missedCount;

        $attendanceRate = $totalVisits > 0 ? ($visitedCount / $totalVisits) * 100 : 0;
        $missedRate     = $totalVisits > 0 ? ($missedCount / $totalVisits) * 100 : 0;
        $cancelledRate  = $totalVisits > 0 ? ($cancelledCount / $totalVisits) * 100 : 0;

        $periodLength = max(1, (int) $from->diffInDays($until) + 1);
        $prevUntil = $from->copy()->subDay()->endOfDay();
        $prevFrom  = $prevUntil->copy()->subDays($periodLength - 1)->startOfDay();

        $prevBase      = $this->baseVisitsQuery($prevFrom, $prevUntil);
        $prevTotal     = (clone $prevBase)->count();
        $prevVisited   = (clone $prevBase)->where('visits.status', 'visited')->count();
        $prevMissed    = (clone $prevBase)->where('visits.status', 'missed')->count();
        $prevCancelled = (clone $prevBase)->where('visits.status', 'cancelled')->count();

        $prevAttendanceRate = $prevTotal > 0 ? round($prevVisited / $prevTotal * 100, 1) : 0.0;
        $prevMissedRate     = $prevTotal > 0 ? round($prevMissed / $prevTotal * 100, 1) : 0.0;
        $prevCancelledRate  = $prevTotal > 0 ? round($prevCancelled / $prevTotal * 100, 1) : 0.0;

        $deltaCount = fn(int $cur, int $prev): ?string =>
            $prev > 0 ? abs(round(($cur - $prev) / $prev * 100, 1)) . '%' : null;
        $deltaRate = fn(float $cur, float $prev): string =>
            abs(round($cur - $prev, 1)) . '%';
        $dir = fn($cur, $prev): string => $cur >= $prev ? 'up' : 'down';

        $cards = [
            [
                'label'      => 'Total Visits',
                'value'      => $totalVisits,
                'suffix'     => null,
                'delta'      => $deltaCount($totalVisits, $prevTotal),
                'deltaLabel' => 'vs prev period',
                'direction'  => $dir($totalVisits, $prevTotal),
                'sentiment'  => 'neutral',
            ],
            [
                'label'      => 'Attendance Rate',
                'value'      => number_format(round($attendanceRate, 1), 1),
                'suffix'     => '%',
                'delta'      => $deltaRate(round($attendanceRate, 1), $prevAttendanceRate),
                'deltaLabel' => 'vs prev period',
                'direction'  => $dir($attendanceRate, $prevAttendanceRate),
                'sentiment'  => 'positive',
            ],
            [
                'label'      => 'Missed Rate',
                'value'      => number_format(round($missedRate, 1), 1),
                'suffix'     => '%',
                'delta'      => $deltaRate(round($missedRate, 1), $prevMissedRate),
                'deltaLabel' => 'vs prev period',
                'direction'  => $dir($missedRate, $prevMissedRate),
                'sentiment'  => 'negative',
            ],
            [
                'label'      => 'Cancelled Rate',
                'value'      => number_format(round($cancelledRate, 1), 1),
                'suffix'     => '%',
                'delta'      => $deltaRate(round($cancelledRate, 1), $prevCancelledRate),
                'deltaLabel' => 'vs prev period',
                'direction'  => $dir($cancelledRate, $prevCancelledRate),
                'sentiment'  => 'negative',
            ],
            [
                'label'      => 'No-show Count',
                'value'      => $noShowCount,
                'suffix'     => null,
                'delta'      => $deltaCount($noShowCount, $prevMissed),
                'deltaLabel' => 'vs prev period',
                'direction'  => $dir($noShowCount, $prevMissed),
                'sentiment'  => 'negative',
            ],
        ];

        $trainerPerformance = ScheduleOccurrence::query()
            ->selectRaw("staff.id as trainer_id, staff.full_name as trainer_name, COUNT(DISTINCT schedule_occurrences.id) as sessions_count, SUM(COALESCE(schedule_occurrences.max_participants, schedules.max_participants, 0)) as total_capacity, SUM(CASE WHEN visits.status = 'visited' THEN 1 ELSE 0 END) as total_visited")
            ->join('schedules', 'schedules.id', '=', 'schedule_occurrences.schedule_id')
            ->join('staff', 'staff.id', '=', 'schedules.trainer_id')
            ->leftJoin('visits', function ($join) {
                $join->on('visits.occurrence_id', '=', 'schedule_occurrences.id');
            })
            ->whereBetween('schedule_occurrences.date', [$from->toDateString(), $until->toDateString()])
            ->when($trainerId, fn (Builder $query) => $query->whereIn('schedules.trainer_id', $trainerId))
            ->when($hallId, fn (Builder $query) => $query->whereIn('schedules.hall_id', $hallId))
            ->when($activityId, fn (Builder $query) => $query->whereIn('schedules.activity_id', $activityId));

        if ($dayOfWeek) {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'pgsql') {
                $trainerPerformance->whereRaw(
                    "LOWER(TO_CHAR(schedule_occurrences.date, 'FMDay')) = ANY(?::text[])",
                    ['{' . implode(',', array_map('strtolower', $dayOfWeek)) . '}']
                );
            } else {
                $trainerPerformance->whereIn(
                    DB::raw('LOWER(DAYNAME(schedule_occurrences.date))'),
                    array_map('strtolower', $dayOfWeek)
                );
            }
        }

        $trainerPerformance = $trainerPerformance
            ->groupBy('staff.id', 'staff.full_name')
            ->get()
            ->map(function ($row) {
                $capacity = (int) $row->total_capacity;
                $visited  = (int) $row->total_visited;
                $sessions = (int) $row->sessions_count;

                return (object) [
                    'trainer_id'       => (int) $row->trainer_id,
                    'trainer_name'     => $row->trainer_name,
                    'sessions_count'   => $sessions,
                    'total_visited'    => $visited,
                    'total_capacity'   => $capacity,
                    'fill_rate'        => $capacity > 0 ? round(($visited / $capacity) * 100, 1) : 0.0,
                    'avg_participants' => $sessions > 0 ? round($visited / $sessions, 1) : 0.0,
                ];
            })
            ->filter(fn ($row) => $row->sessions_count > 0)
            ->values();

        $topTrainers = $trainerPerformance->sortByDesc(fn ($row) => $row->fill_rate)->take(5)->values();
        $lowestTrainers = $trainerPerformance->sortBy(fn ($row) => $row->fill_rate)->take(5)->values();
        $hasData = $totalVisits > 0 || $trainerPerformance->isNotEmpty();

        $checkinsByHour = CustomerCheckin::query()
            ->selectRaw('EXTRACT(HOUR FROM checked_in_at)::int AS hour, COUNT(*) AS cnt')
            ->whereBetween('checked_in_at', [$from, $until])
            ->whereNotNull('schedule_id')
            ->when($activityId, fn (Builder $q) => $q->whereHas('customerSubscription.subscription', fn (Builder $s) => $s->whereIn('activity_id', $activityId)))
            ->when($hallId, fn (Builder $q) => $q->whereHas('schedule', fn (Builder $s) => $s->whereIn('hall_id', $hallId)))
            ->groupByRaw('EXTRACT(HOUR FROM checked_in_at)::int')
            ->pluck('cnt', 'hour');

        $totalCheckins = $checkinsByHour->sum();
        $peakHour      = $checkinsByHour->isEmpty() ? null : $checkinsByHour->sortDesc()->keys()->first();
        $peakCount     = $peakHour !== null ? (int) $checkinsByHour[$peakHour] : 0;
        $peakHourLabel = $peakHour !== null ? sprintf('%02d:00', $peakHour) : '—';

        return [
            'cards'          => $cards,
            'topTrainers'    => $topTrainers,
            'lowestTrainers' => $lowestTrainers,
            'hasData'        => $hasData,
            'rangeLabel'     => $from->toDateString() . ' -> ' . $until->toDateString(),
            'from'           => $from->toDateString(),
            'until'          => $until->toDateString(),
            'trainerId'      => $trainerId,
            'hallId'         => $hallId,
            'activityId'     => $activityId,
            'status'         => $status,
            'dayOfWeek'      => $dayOfWeek,
            'peakMetrics'    => [
                'totalCheckins' => $totalCheckins,
                'peakHour'      => $peakHourLabel,
                'peakCount'     => $peakCount,
            ],
        ];
    }

    private function baseVisitsQuery(Carbon $from, Carbon $until): Builder
    {
        $trainerId  = !empty($this->data['trainerId'])  ? array_map('intval', (array) $this->data['trainerId'])  : [];
        $hallId     = !empty($this->data['hallId'])     ? array_map('intval', (array) $this->data['hallId'])     : [];
        $activityId = !empty($this->data['activityId']) ? array_map('intval', (array) $this->data['activityId']) : [];
        $status     = !empty($this->data['status'])     ? (array) $this->data['status']                          : [];
        $dayOfWeek  = !empty($this->data['dayOfWeek'])  ? (array) $this->data['dayOfWeek']                       : [];

        $query = Visit::query()
            ->join('schedules', 'schedules.id', '=', 'visits.schedule_id')
            ->leftJoin('schedule_occurrences', 'schedule_occurrences.id', '=', 'visits.occurrence_id')
            ->whereBetween(
                DB::raw('COALESCE(schedule_occurrences.date, DATE(visits.visited_at))'),
                [$from->toDateString(), $until->toDateString()]
            )
            ->when($trainerId, fn (Builder $q) => $q->whereIn('visits.trainer_id', $trainerId))
            ->when($hallId, fn (Builder $q) => $q->whereIn('schedules.hall_id', $hallId))
            ->when($activityId, fn (Builder $q) => $q->whereIn('schedules.activity_id', $activityId))
            ->when($status, fn (Builder $q) => $q->whereIn('visits.status', $status));

        if ($dayOfWeek) {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'pgsql') {
                $query->whereRaw(
                    "LOWER(TO_CHAR(COALESCE(schedule_occurrences.date, DATE(visits.visited_at)), 'FMDay')) = ANY(?::text[])",
                    ['{' . implode(',', array_map('strtolower', $dayOfWeek)) . '}']
                );
            } else {
                $query->whereIn(
                    DB::raw('LOWER(DAYNAME(COALESCE(schedule_occurrences.date, DATE(visits.visited_at))))'),
                    array_map('strtolower', $dayOfWeek)
                );
            }
        }

        return $query;
    }

    private function resolveDateRange(): array
    {
        $today = Carbon::today();
        $from  = !empty($this->data['from'])  ? Carbon::parse($this->data['from'])  : $today->copy()->startOfMonth();
        $until = !empty($this->data['until']) ? Carbon::parse($this->data['until']) : $today->copy()->endOfMonth();

        if ($from->gt($until)) {
            [$from, $until] = [$until, $from];
        }

        return [$from->startOfDay(), $until->endOfDay()];
    }

    private function syncPeriodDates(): void
    {
        $today  = Carbon::today();
        $period = $this->data['period'] ?? 'month';

        switch ($period) {
            case 'today':
                $this->data['from']  = $today->toDateString();
                $this->data['until'] = $today->toDateString();
                break;
            case 'week':
                $this->data['from']  = $today->copy()->startOfWeek()->toDateString();
                $this->data['until'] = $today->copy()->endOfWeek()->toDateString();
                break;
            case 'range':
                $this->data['from']  ??= $today->toDateString();
                $this->data['until'] ??= $today->toDateString();
                break;
            case 'month':
            default:
                $this->data['from']  = $today->copy()->startOfMonth()->toDateString();
                $this->data['until'] = $today->copy()->endOfMonth()->toDateString();
                break;
        }
    }
}
