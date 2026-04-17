<?php

namespace App\Services\Analytics;

use App\Models\Customer;
use App\Models\CustomerSubscription;
use App\Models\Payment;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KpiCalculator
{
    public function __construct(
        private Carbon $from,
        private Carbon $until,
    ) {
    }

    public function revenue(): float
    {
        return (float) Payment::query()
            ->whereBetween('created_at', [$this->from, $this->until])
            ->whereIn('status', ['paid', 'partial'])
            ->sum('amount');
    }

    public function newCustomers(): int
    {
        return (int) Customer::query()
            ->whereBetween('created_at', [$this->from, $this->until])
            ->count();
    }

    public function activeCustomers(): int
    {
        return (int) CustomerSubscription::query()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $this->until->toDateString())
            ->whereDate('end_date', '>=', $this->from->toDateString())
            ->distinct('customer_id')
            ->count('customer_id');
    }

    public function debt(): float
    {
        return (float) CustomerSubscription::query()
            ->whereDate('start_date', '<=', $this->until->toDateString())
            ->whereDate('end_date', '>=', $this->from->toDateString())
            ->sum('debt');
    }

    public function statusSummary(): array
    {
        $weekFrom = $this->from->copy()->startOfDay();
        $weekUntil = $this->until->copy()->endOfDay();

        $statusRows = Visit::query()
            ->selectRaw('status, COUNT(*) as total')
            ->whereBetween(DB::raw('DATE(COALESCE(visited_at, created_at))'), [$weekFrom->toDateString(), $weekUntil->toDateString()])
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $visited = (int) ($statusRows['visited']->total ?? 0);
        $missed = (int) ($statusRows['missed']->total ?? 0);
        $cancelled = (int) ($statusRows['cancelled']->total ?? 0);
        $total = max(1, $visited + $missed + $cancelled);

        return [
            'visited' => $visited,
            'missed' => $missed,
            'cancelled' => $cancelled,
            'visitedPercent' => round(($visited / $total) * 100, 1),
        ];
    }

    public function sparklineRevenue(): array
    {
        $rows = Payment::query()
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->whereBetween('created_at', [$this->from, $this->until])
            ->whereIn('status', ['paid', 'partial'])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy('date');

        return $this->dailySeries(fn (string $date) => (float) ($rows[$date]->total ?? 0));
    }

    public function sparklineNewCustomers(): array
    {
        $rows = Customer::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->whereBetween('created_at', [$this->from, $this->until])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy('date');

        return $this->dailySeries(fn (string $date) => (float) ($rows[$date]->total ?? 0));
    }

    public function sparklineActiveCustomers(): array
    {
        return $this->dailySeries(function (string $date) {
            return (float) CustomerSubscription::query()
                ->where('status', 'active')
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->distinct('customer_id')
                ->count('customer_id');
        });
    }

    public function sparklineDebt(): array
    {
        return $this->dailySeries(function (string $date) {
            return (float) CustomerSubscription::query()
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->sum('debt');
        });
    }

    public static function delta(float|int $current, float|int $previous): array
    {
        $current = (float) $current;
        $previous = (float) $previous;

        if ($previous == 0.0) {
            if ($current == 0.0) {
                return ['direction' => 'flat', 'percent' => 0.0];
            }

            return ['direction' => 'up', 'percent' => 100.0];
        }

        $percent = (($current - $previous) / abs($previous)) * 100;

        return [
            'direction' => $percent > 0 ? 'up' : ($percent < 0 ? 'down' : 'flat'),
            'percent' => round(abs($percent), 1),
        ];
    }

    private function dailySeries(callable $resolver): array
    {
        $series = [];
        $cursor = $this->from->copy();

        while ($cursor->lte($this->until)) {
            $date = $cursor->toDateString();
            $series[] = (float) $resolver($date);
            $cursor->addDay();
        }

        return $series;
    }
}



