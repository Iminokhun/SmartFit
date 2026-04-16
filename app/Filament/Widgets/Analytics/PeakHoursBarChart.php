<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\CustomerCheckin;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class PeakHoursBarChart extends ApexChartWidget
{
    protected static ?string $chartId = 'peakHoursBarChart';
    protected static ?string $heading = null;

    public ?string $from       = null;
    public ?string $until      = null;
    public ?int    $activityId = null;
    public ?int    $hallId     = null;

    protected function getOptions(): array
    {
        [$from, $until] = $this->resolveDateRange();

        $rows = CustomerCheckin::query()
            ->selectRaw('EXTRACT(HOUR FROM checked_in_at)::int as hour, COUNT(*) as total')
            ->whereBetween('checked_in_at', [$from, $until])
            ->when($this->activityId, fn ($q) => $q->whereHas('schedule', fn ($s) => $s->where('activity_id', $this->activityId)))
            ->when($this->hallId, fn ($q) => $q->whereHas('schedule', fn ($s) => $s->where('hall_id', $this->hallId)))
            ->groupByRaw('EXTRACT(HOUR FROM checked_in_at)::int')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        $peakHour = $rows->sortByDesc('total')->first()?->hour;

        $hours      = range(6, 23);
        $data       = [];
        $colors     = [];

        foreach ($hours as $h) {
            $data[]   = (int) ($rows[$h]->total ?? 0);
            $colors[] = ($peakHour !== null && (int) $peakHour === $h) ? '#ef4444' : '#3b82f6';
        }

        $labels = array_map(fn ($h) => sprintf('%02d:00', $h), $hours);

        return [
            'chart' => [
                'type'       => 'bar',
                'height'     => 280,
                'toolbar'    => ['show' => false],
                'fontFamily' => 'Manrope, Segoe UI, Helvetica Neue, Arial, sans-serif',
                'foreColor'  => '#64748b',
            ],
            'series' => [
                ['name' => 'Check-ins', 'data' => $data],
            ],
            'colors'      => $colors,
            'dataLabels'  => ['enabled' => false],
            'plotOptions' => [
                'bar' => [
                    'distributed'  => true,
                    'borderRadius' => 4,
                    'columnWidth'  => '60%',
                ],
            ],
            'xaxis' => [
                'categories' => $labels,
                'labels'     => ['rotate' => -45, 'style' => ['fontSize' => '11px']],
                'axisBorder' => ['show' => false],
                'axisTicks'  => ['show' => false],
            ],
            'yaxis' => ['labels' => []],
            'grid' => [
                'borderColor'     => '#f1f5f9',
                'strokeDashArray' => 4,
            ],
            'legend'  => ['show' => false],
            'tooltip' => [],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
{
    yaxis: {
        labels: { formatter: function(val) { return Math.round(val); } }
    },
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
