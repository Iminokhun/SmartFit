<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Visit;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class AttendanceStatusSplitChart extends ApexChartWidget
{
    protected static ?string $chartId = 'attendanceStatusSplitChart';
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
            ->selectRaw('visits.status as status, COUNT(*) as total')
            ->groupBy('visits.status')
            ->get()
            ->keyBy('status');

        return [
            'chart' => [
                'type' => 'donut',
                'height' => 320,
                'fontFamily' => 'Manrope, Segoe UI, Helvetica Neue, Arial, sans-serif',
                'foreColor' => '#64748b',
            ],
            'series' => [
                (int) ($rows['visited']->total ?? 0),
                (int) ($rows['missed']->total ?? 0),
                (int) ($rows['cancelled']->total ?? 0),
            ],
            'labels' => ['Visited', 'Missed', 'Cancelled'],
            'colors' => ['#15803d', '#b45309', '#dc2626'],
            'legend' => ['position' => 'bottom', 'fontSize' => '12px'],
            'dataLabels' => ['enabled' => false],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
{
    tooltip: {
        y: {
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

