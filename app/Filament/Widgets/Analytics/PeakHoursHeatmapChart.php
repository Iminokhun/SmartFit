<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\CustomerCheckin;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class PeakHoursHeatmapChart extends ApexChartWidget
{
    protected static ?string $chartId = 'peakHoursHeatmapChart';
    protected static ?string $heading = null;

    public ?string $from       = null;
    public ?string $until      = null;
    public array $activityId = [];
    public array $hallId     = [];

    protected function getOptions(): array
    {
        [$from, $until] = $this->resolveDateRange();

        $rows = CustomerCheckin::query()
            ->selectRaw('EXTRACT(DOW FROM checked_in_at)::int as dow, EXTRACT(HOUR FROM checked_in_at)::int as hour, COUNT(*) as total')
            ->whereBetween('checked_in_at', [$from, $until])
            ->when($this->activityId, fn ($q) => $q->whereHas('schedule', fn ($s) => $s->whereIn('activity_id', $this->activityId)))
            ->when($this->hallId, fn ($q) => $q->whereHas('schedule', fn ($s) => $s->whereIn('hall_id', $this->hallId)))
            ->groupByRaw('EXTRACT(DOW FROM checked_in_at)::int, EXTRACT(HOUR FROM checked_in_at)::int')
            ->get();

        // Build lookup: $map[dow][hour] = count
        // PostgreSQL DOW: 0=Sun, 1=Mon … 6=Sat
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->dow][(int) $row->hour] = (int) $row->total;
        }

        // Mon-first order: 1,2,3,4,5,6,0
        $days   = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun'];
        $hours  = range(6, 23);
        $series = [];

        foreach ($days as $dow => $label) {
            $data = [];
            foreach ($hours as $h) {
                $data[] = ['x' => sprintf('%02d:00', $h), 'y' => (int) ($map[$dow][$h] ?? 0)];
            }
            $series[] = ['name' => $label, 'data' => $data];
        }

        return [
            'chart' => [
                'type'       => 'heatmap',
                'height'     => 320,
                'toolbar'    => ['show' => false],
                'fontFamily' => 'Manrope, Segoe UI, Helvetica Neue, Arial, sans-serif',
                'foreColor'  => '#64748b',
            ],
            'series'      => $series,
            'colors'      => ['#3b82f6'],
            'dataLabels'  => ['enabled' => false],
            'plotOptions' => [
                'heatmap' => [
                    'shadeIntensity' => 0.6,
                    'radius'         => 2,
                    'colorScale'     => [
                        'ranges' => [
                            ['from' => 0,  'to' => 0,  'color' => '#f1f5f9', 'name' => 'None'],
                            ['from' => 1,  'to' => 5,  'color' => '#bfdbfe', 'name' => 'Low'],
                            ['from' => 6,  'to' => 15, 'color' => '#60a5fa', 'name' => 'Medium'],
                            ['from' => 16, 'to' => 9999, 'color' => '#1d4ed8', 'name' => 'High'],
                        ],
                    ],
                ],
            ],
            'xaxis' => [
                'labels' => ['rotate' => -45, 'style' => ['fontSize' => '10px']],
                'axisBorder' => ['show' => false],
                'axisTicks'  => ['show' => false],
            ],
            'tooltip' => [],
            'legend' => ['show' => false],
            'grid'   => ['padding' => ['right' => 10]],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
{
    tooltip: {
        y: { formatter: function(val) { return val + ' check-ins'; } }
    }
}
JS);
    }

    private function resolveDateRange(): array
    {
        $from  = $this->from  ? Carbon::parse($this->from)  : Carbon::today();
        $until = $this->until ? Carbon::parse($this->until) : Carbon::today();

        if ($from->gt($until)) {
            [$from, $until] = [$until, $from];
        }

        return [$from->startOfDay(), $until->endOfDay()];
    }
}
