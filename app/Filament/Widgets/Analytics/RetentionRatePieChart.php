<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Customer;
use App\Models\CustomerSubscription;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RetentionRatePieChart extends ApexChartWidget
{
    protected static ?string $chartId = 'retentionRatePieChart';
    protected static ?string $heading = null;

    public ?string $from  = null;
    public ?string $until = null;
    public ?int $activityId = null;

    protected function getOptions(): array
    {
        [$from, $until] = $this->resolveDateRange();

        $expiringIds = CustomerSubscription::query()
            ->whereBetween('end_date', [$from->toDateString(), $until->toDateString()])
            ->when($this->activityId, fn ($q) => $q->whereHas('subscription', fn ($s) => $s->where('activity_id', $this->activityId)))
            ->distinct()
            ->pluck('customer_id');

        $churned = 0;
        if ($expiringIds->isNotEmpty()) {
            $churned = Customer::query()
                ->whereIn('id', $expiringIds)
                ->whereDoesntHave('subscriptions', fn ($q) =>
                    $q->whereIn('status', ['active', 'pending'])
                      ->whereDate('end_date', '>=', today())
                )
                ->count();
        }

        $retained = max(0, $expiringIds->count() - $churned);

        return [
            'chart' => [
                'type'       => 'donut',
                'height'     => 280,
                'fontFamily' => 'Manrope, Segoe UI, Helvetica Neue, Arial, sans-serif',
                'foreColor'  => '#64748b',
            ],
            'series' => [$churned, $retained],
            'labels' => ['Churned', 'Retained'],
            'colors' => ['#ef4444', '#22c55e'],
            'legend' => [
                'position'        => 'bottom',
                'horizontalAlign' => 'center',
                'fontSize'        => '12px',
            ],
            'dataLabels' => ['enabled' => true],
            'plotOptions' => [
                'pie' => [
                    'donut' => ['size' => '65%'],
                ],
            ],
            'tooltip' => [],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
{
    dataLabels: {
        formatter: function(val) { return val.toFixed(1) + '%'; }
    },
    tooltip: {
        y: { formatter: function(val) { return val + ' customers'; } }
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
