<?php

namespace App\Filament\Pages\Analytics;

use App\Enums\PaymentMethod;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\CustomerSubscription;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class Clients extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedUsers;
    protected static string|null|\UnitEnum $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 3;
    protected static ?string $title = 'Client Analytics';
    protected static ?string $navigationLabel = 'Clients';
    protected static ?string $slug = 'analytics/clients';

    protected string $view = 'filament.pages.analytics.clients';

    public string $period = 'month';
    public ?string $from = null;
    public ?string $until = null;
    public ?int $activityId = null;
    public ?string $paymentMethod = null;
    public ?string $paymentStatus = null;

    public function mount(): void
    {
        $this->syncPeriodDates();
    }

    public function updatedPeriod(): void
    {
        $this->syncPeriodDates();
    }

    public function resetFilters(): void
    {
        $this->period = 'month';
        $this->activityId = null;
        $this->paymentMethod = null;
        $this->paymentStatus = null;
        $this->syncPeriodDates();
    }

    protected function getViewData(): array
    {
        [$from, $until] = $this->resolveDateRange();
        [$prevFrom, $prevUntil] = $this->resolvePreviousDateRange($from, $until);
        $revenueStatuses = $this->resolveRevenueStatuses();

        $paymentsBase = Payment::query()
            ->whereBetween('created_at', [$from, $until])
            ->when($this->paymentMethod, fn (Builder $query) => $query->where('method', $this->paymentMethod))
            ->when($this->paymentStatus, fn (Builder $query) => $query->where('status', $this->paymentStatus))
            ->when($this->activityId, function (Builder $query) {
                $query->whereHas('customerSubscription.subscription', function (Builder $subQuery) {
                    $subQuery->where('activity_id', $this->activityId);
                });
            });
        $paymentsPrevBase = Payment::query()
            ->whereBetween('created_at', [$prevFrom, $prevUntil])
            ->when($this->paymentMethod, fn (Builder $query) => $query->where('method', $this->paymentMethod))
            ->when($this->paymentStatus, fn (Builder $query) => $query->where('status', $this->paymentStatus))
            ->when($this->activityId, function (Builder $query) {
                $query->whereHas('customerSubscription.subscription', function (Builder $subQuery) {
                    $subQuery->where('activity_id', $this->activityId);
                });
            });

        $newClients = Customer::query()
            ->whereBetween('created_at', [$from, $until])
            ->count();
        $newClientsPrev = Customer::query()
            ->whereBetween('created_at', [$prevFrom, $prevUntil])
            ->count();

        $activeBySubscription = CustomerSubscription::query()
            ->select('customer_id')
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $until->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->when($this->activityId, function (Builder $query) {
                $query->whereHas('subscription', function (Builder $subQuery) {
                    $subQuery->where('activity_id', $this->activityId);
                });
            })
            ->distinct()
            ->pluck('customer_id');
        $activeBySubscriptionPrev = CustomerSubscription::query()
            ->select('customer_id')
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $prevUntil->toDateString())
            ->whereDate('end_date', '>=', $prevFrom->toDateString())
            ->when($this->activityId, function (Builder $query) {
                $query->whereHas('subscription', function (Builder $subQuery) {
                    $subQuery->where('activity_id', $this->activityId);
                });
            })
            ->distinct()
            ->pluck('customer_id');

        $activeByPayment = (clone $paymentsBase)
            ->select('customer_id')
            ->distinct()
            ->pluck('customer_id');
        $activeByPaymentPrev = (clone $paymentsPrevBase)
            ->select('customer_id')
            ->distinct()
            ->pluck('customer_id');

        $activeClients = $activeBySubscription
            ->merge($activeByPayment)
            ->unique()
            ->count();
        $activeClientsPrev = $activeBySubscriptionPrev
            ->merge($activeByPaymentPrev)
            ->unique()
            ->count();

        $revenue = 0.0;
        $payingClients = 0;
        $revenuePrev = 0.0;
        $payingClientsPrev = 0;

        if (! empty($revenueStatuses)) {
            $revenue = (clone $paymentsBase)
                ->whereIn('status', $revenueStatuses)
                ->sum('amount');
            $revenuePrev = (clone $paymentsPrevBase)
                ->whereIn('status', $revenueStatuses)
                ->sum('amount');

            $payingClients = (clone $paymentsBase)
                ->whereIn('status', $revenueStatuses)
                ->distinct('customer_id')
                ->count('customer_id');
            $payingClientsPrev = (clone $paymentsPrevBase)
                ->whereIn('status', $revenueStatuses)
                ->distinct('customer_id')
                ->count('customer_id');
        }

        $arpu = $payingClients > 0 ? $revenue / $payingClients : 0;
        $arpuPrev = $payingClientsPrev > 0 ? $revenuePrev / $payingClientsPrev : 0;

        $subscriptionsWindow = CustomerSubscription::query()
            ->whereDate('start_date', '<=', $until->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->when($this->activityId, function (Builder $query) {
                $query->whereHas('subscription', function (Builder $subQuery) {
                    $subQuery->where('activity_id', $this->activityId);
                });
            });

        $clientHealth = [
            'paid' => (clone $subscriptionsWindow)->where('payment_status', 'paid')->count(),
            'partial' => (clone $subscriptionsWindow)->where('payment_status', 'partial')->count(),
            'unpaid' => (clone $subscriptionsWindow)->where('payment_status', 'unpaid')->count(),
            'debt' => (float) (clone $subscriptionsWindow)->sum('debt'),
        ];
        $clientHealthTotal = max(1, $clientHealth['paid'] + $clientHealth['partial'] + $clientHealth['unpaid']);
        $hasData = ($newClients + $activeClients + $payingClients) > 0 || $clientHealth['debt'] > 0;

        return [
            'periodOptions' => [
                'today' => 'Today',
                'week' => 'This week',
                'month' => 'This month',
                'range' => 'Custom range',
            ],
            'activities' => Activity::query()->orderBy('name')->pluck('name', 'id')->all(),
            'paymentMethods' => PaymentMethod::options(),
            'paymentStatuses' => [
                'paid' => 'Paid',
                'partial' => 'Partial',
                'pending' => 'Pending',
                'failed' => 'Failed',
            ],
            'metrics' => [
                'newClients' => $newClients,
                'activeClients' => $activeClients,
                'payingClients' => $payingClients,
                'arpu' => $arpu,
            ],
            'kpiDeltas' => [
                'newClients' => $this->delta($newClients, $newClientsPrev),
                'activeClients' => $this->delta($activeClients, $activeClientsPrev),
                'payingClients' => $this->delta($payingClients, $payingClientsPrev),
                'arpu' => $this->delta($arpu, $arpuPrev),
            ],
            'clientHealth' => $clientHealth,
            'clientHealthTotal' => $clientHealthTotal,
            'hasData' => $hasData,
            'rangeLabel' => $from->toDateString() . ' -> ' . $until->toDateString(),
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

    private function resolvePreviousDateRange(Carbon $from, Carbon $until): array
    {
        $days = $from->diffInDays($until) + 1;
        $prevUntil = $from->copy()->subDay()->endOfDay();
        $prevFrom = $prevUntil->copy()->subDays($days - 1)->startOfDay();

        return [$prevFrom, $prevUntil];
    }

    private function delta(float|int $current, float|int $previous): array
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

    private function resolveRevenueStatuses(): array
    {
        $allowed = ['paid', 'partial'];
        if ($this->paymentStatus) {
            return array_values(array_intersect($allowed, [$this->paymentStatus]));
        }

        return $allowed;
    }

    private function syncPeriodDates(): void
    {
        $today = Carbon::today();

        switch ($this->period) {
            case 'today':
                $this->from = $today->toDateString();
                $this->until = $today->toDateString();
                break;
            case 'week':
                $this->from = $today->copy()->startOfWeek()->toDateString();
                $this->until = $today->copy()->endOfWeek()->toDateString();
                break;
            case 'range':
                $this->from ??= $today->toDateString();
                $this->until ??= $today->toDateString();
                break;
            case 'month':
            default:
                $this->from = $today->copy()->startOfMonth()->toDateString();
                $this->until = $today->copy()->endOfMonth()->toDateString();
                break;
        }
    }
}
