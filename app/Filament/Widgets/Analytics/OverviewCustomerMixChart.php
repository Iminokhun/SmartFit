<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Customer;
use App\Models\CustomerSubscription;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class OverviewCustomerMixChart extends ApexChartWidget
{
    protected static ?string $chartId = 'overviewCustomerMixChart';
    protected static ?string $heading = null;

    public ?string $from = null;
    public ?string $until = null;

    protected function getOptions(): array
    {
        [$from, $until] = $this->resolveDateRange();

        $newCustomers = Customer::query()
            ->whereBetween('created_at', [$from, $until])
            ->count();

        $activeCustomers = CustomerSubscription::query()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $until->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->distinct('customer_id')
            ->count('customer_id');

        return [
            'chart' => [
                'type' => 'donut',
                'height' => 280,
                'fontFamily' => 'Manrope, Segoe UI, Helvetica Neue, Arial, sans-serif',
            ],
            'series' => [(int) $newCustomers, (int) $activeCustomers],
            'labels' => ['New customers', 'Active customers'],
            'colors' => ['#f59e0b', '#16a34a'],
            'legend' => [
                'position' => 'bottom',
                'fontSize' => '12px',
            ],
            'dataLabels' => ['enabled' => false],
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '68%',
                    ],
                ],
            ],
        ];
    }

    private function resolveDateRange(): array
    {
        $from = $this->from ? Carbon::parse($this->from) : Carbon::today()->startOfMonth();
        $until = $this->until ? Carbon::parse($this->until) : Carbon::today()->endOfMonth();

        if ($from->gt($until)) {
            [$from, $until] = [$until, $from];
        }

        return [$from->startOfDay(), $until->endOfDay()];
    }
}
