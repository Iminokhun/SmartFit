<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class FinancePendingFailedPieChart extends ApexChartWidget
{
    protected static ?string $chartId = 'financePendingFailedPieChart';
    protected static ?string $heading = null;

    public ?string $from = null;
    public ?string $until = null;
    public ?int $activityId = null;
    public ?string $paymentMethod = null;

    protected function getOptions(): array
    {
        [$from, $until] = $this->resolveDateRange();

        $rows = Payment::query()
            ->selectRaw('payments.status as status, COUNT(*) as total')
            ->join('customer_subscriptions', 'customer_subscriptions.id', '=', 'payments.customer_subscription_id')
            ->join('subscriptions', 'subscriptions.id', '=', 'customer_subscriptions.subscription_id')
            ->whereBetween('payments.created_at', [$from, $until])
            ->whereIn('payments.status', ['pending', 'failed'])
            ->when($this->paymentMethod, fn (Builder $query) => $query->where('payments.method', $this->paymentMethod))
            ->when($this->activityId, fn (Builder $query) => $query->where('subscriptions.activity_id', $this->activityId))
            ->groupBy('payments.status')
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
                (int) ($rows['pending']->total ?? 0),
                (int) ($rows['failed']->total ?? 0),
            ],
            'labels' => ['Pending', 'Failed'],
            'colors' => ['#f59e0b', '#dc2626'],
            'legend' => ['position' => 'bottom', 'fontSize' => '12px'],
            'dataLabels' => ['enabled' => false],
        ];
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

