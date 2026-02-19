<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Visit;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class AttendanceVisitsTrendChart extends ApexChartWidget
{
    protected static ?string $chartId = 'attendanceVisitsTrendChart';
    protected static ?string $heading = null;

    public ?string $from = null;
    public ?string $until = null;
    public ?int $trainerId = null;
    public ?int $hallId = null;
    public ?int $activityId = null;
    public ?string $status = null;
    public ?string $dayOfWeek = null;

    protected function getOptions(): array
    {
        [$from, $until] = $this->resolveDateRange();

        $rows = $this->baseVisitsQuery($from, $until)
            ->selectRaw("DATE(COALESCE(schedule_occurrences.date, DATE(visits.visited_at))) as date")
            ->selectRaw("SUM(CASE WHEN visits.status = 'visited' THEN 1 ELSE 0 END) as visited_count")
            ->selectRaw("SUM(CASE WHEN visits.status = 'missed' THEN 1 ELSE 0 END) as missed_count")
            ->selectRaw("SUM(CASE WHEN visits.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count")
            ->groupBy(DB::raw("DATE(COALESCE(schedule_occurrences.date, DATE(visits.visited_at)))"))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $visitedSeries = [];
        $missedSeries = [];
        $cancelledSeries = [];

        $cursor = $from->copy();
        while ($cursor->lte($until)) {
            $date = $cursor->toDateString();
            $labels[] = $cursor->format('M d');
            $visitedSeries[] = (int) ($rows[$date]->visited_count ?? 0);
            $missedSeries[] = (int) ($rows[$date]->missed_count ?? 0);
            $cancelledSeries[] = (int) ($rows[$date]->cancelled_count ?? 0);
            $cursor->addDay();
        }

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 320,
                'toolbar' => ['show' => false],
                'fontFamily' => 'Manrope, Segoe UI, Helvetica Neue, Arial, sans-serif',
                'foreColor' => '#64748b',
            ],
            'series' => [
                ['name' => 'Visited', 'data' => $visitedSeries],
                ['name' => 'Missed', 'data' => $missedSeries],
                ['name' => 'Cancelled', 'data' => $cancelledSeries],
            ],
            'colors' => ['#15803d', '#b45309', '#dc2626'],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'columnWidth' => '58%',
                    'borderRadius' => 4,
                ],
            ],
            'dataLabels' => ['enabled' => false],
            'stroke' => ['show' => false],
            'grid' => [
                'borderColor' => '#e2e8f0',
                'strokeDashArray' => 4,
            ],
            'xaxis' => [
                'categories' => $labels,
                'labels' => ['rotate' => -45],
                'axisBorder' => ['show' => false],
                'axisTicks' => ['show' => false],
            ],
            'yaxis' => [
                'labels' => ['minWidth' => 28],
            ],
            'legend' => [
                'position' => 'top',
                'horizontalAlign' => 'left',
                'fontSize' => '12px',
            ],
            'tooltip' => ['shared' => true, 'intersect' => false],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
{
    yaxis: {
        labels: {
            formatter: function (value) {
                return Number(value).toLocaleString('en-US');
            }
        }
    }
}
JS);
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
}
