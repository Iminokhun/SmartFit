<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SubscriptionsTopPlansChart extends ApexChartWidget
{
    protected static ?string $chartId = 'subscriptionsTopPlans';
    protected static ?string $heading = null;

    public ?string $from = null;
    public ?string $until = null;
    public ?int $activityId = null;
    public ?string $paymentMethod = null;
    public ?string $paymentStatus = null;

    protected function getOptions(): array
    {
        [$from, $until] = $this->resolveDateRange();
        $revenueStatuses = $this->resolveRevenueStatuses();

        $rows = Payment::query()
            ->selectRaw('subscriptions.name as name, SUM(payments.amount) as total')
            ->join('customer_subscriptions', 'customer_subscriptions.id', '=', 'payments.customer_subscription_id')
            ->join('subscriptions', 'subscriptions.id', '=', 'customer_subscriptions.subscription_id')
            ->whereBetween('payments.created_at', [$from, $until])
            ->whereIn('payments.status', $revenueStatuses)
            ->when($this->paymentMethod, fn (Builder $query) => $query->where('payments.method', $this->paymentMethod))
            ->when($this->paymentStatus, fn (Builder $query) => $query->where('payments.status', $this->paymentStatus))
            ->when($this->activityId, fn (Builder $query) => $query->where('subscriptions.activity_id', $this->activityId))
            ->groupBy('subscriptions.name')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        return [
            'chart' => [
                'type' => 'donut',
                'height' => 320,
                'fontFamily' => 'Manrope, Segoe UI, Helvetica Neue, Arial, sans-serif',
                'foreColor' => '#64748b',
            ],
            'series' => $rows->pluck('total')->map(fn ($value) => (float) $value)->all(),
            'labels' => $rows->pluck('name')->all(),
            'legend' => ['position' => 'bottom', 'fontSize' => '12px'],
            'dataLabels' => ['enabled' => false],
            'colors' => ['#2563eb', '#0f766e', '#94a3b8', '#38bdf8', '#f59e0b', '#64748b'],
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

    private function resolveRevenueStatuses(): array
    {
        $allowed = ['paid', 'partial'];
        if ($this->paymentStatus) {
            return array_values(array_intersect($allowed, [$this->paymentStatus]));
        }

        return $allowed;
    }
}
