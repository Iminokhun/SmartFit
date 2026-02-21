<?php

namespace App\Filament\Pages\Analytics;

use App\Enums\PaymentMethod;
use App\Models\Activity;
use App\Models\CustomerSubscription;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class Finance extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedBanknotes;
    protected static string|null|\UnitEnum $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 4;
    protected static ?string $title = 'Finance Analytics';
    protected static ?string $navigationLabel = 'Finance';
    protected static ?string $slug = 'analytics/finance';

    protected string $view = 'filament.pages.analytics.finance';

    public string $period = 'month';
    public ?string $from = null;
    public ?string $until = null;
    public ?int $activityId = null;
    public ?string $paymentMethod = null;
    public ?string $paymentStatus = null;
    public ?int $expenseCategoryId = null;

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
        $this->expenseCategoryId = null;
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

        $collectedRevenue = 0.0;
        $collectedRevenuePrev = 0.0;
        if (! empty($revenueStatuses)) {
            $collectedRevenue = (clone $paymentsBase)
                ->whereIn('status', $revenueStatuses)
                ->sum('amount');
            $collectedRevenuePrev = (clone $paymentsPrevBase)
                ->whereIn('status', $revenueStatuses)
                ->sum('amount');
        }

        $expensesBase = Expense::query()
            ->whereBetween('expenses_date', [$from->toDateString(), $until->toDateString()])
            ->when($this->expenseCategoryId, fn (Builder $query) => $query->where('category_id', $this->expenseCategoryId));
        $expensesPrevBase = Expense::query()
            ->whereBetween('expenses_date', [$prevFrom->toDateString(), $prevUntil->toDateString()])
            ->when($this->expenseCategoryId, fn (Builder $query) => $query->where('category_id', $this->expenseCategoryId));

        $expenses = (float) (clone $expensesBase)->sum('amount');
        $expensesPrev = (float) (clone $expensesPrevBase)->sum('amount');
        $netProfit = $collectedRevenue - $expenses;
        $netProfitPrev = $collectedRevenuePrev - $expensesPrev;

        $debt = (float) CustomerSubscription::query()
            ->whereDate('start_date', '<=', $until->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->when($this->activityId, function (Builder $query) {
                $query->whereHas('subscription', function (Builder $subQuery) {
                    $subQuery->where('activity_id', $this->activityId);
                });
            })
            ->sum('debt');
        $debtPrev = (float) CustomerSubscription::query()
            ->whereDate('start_date', '<=', $prevUntil->toDateString())
            ->whereDate('end_date', '>=', $prevFrom->toDateString())
            ->when($this->activityId, function (Builder $query) {
                $query->whereHas('subscription', function (Builder $subQuery) {
                    $subQuery->where('activity_id', $this->activityId);
                });
            })
            ->sum('debt');

        $collectionRate = ($collectedRevenue + $debt) > 0
            ? ($collectedRevenue / ($collectedRevenue + $debt)) * 100
            : 0.0;
        $collectionRatePrev = ($collectedRevenuePrev + $debtPrev) > 0
            ? ($collectedRevenuePrev / ($collectedRevenuePrev + $debtPrev)) * 100
            : 0.0;

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
            'expenseCategories' => ExpenseCategory::query()->orderBy('name')->pluck('name', 'id')->all(),
            'metrics' => [
                'revenue' => $collectedRevenue,
                'expenses' => $expenses,
                'netProfit' => $netProfit,
                'debt' => $debt,
                'collectionRate' => round($collectionRate, 1),
            ],
            'kpiDeltas' => [
                'revenue' => $this->delta($collectedRevenue, $collectedRevenuePrev),
                'expenses' => $this->delta($expenses, $expensesPrev),
                'netProfit' => $this->delta($netProfit, $netProfitPrev),
                'debt' => $this->delta($debt, $debtPrev),
                'collectionRate' => $this->delta($collectionRate, $collectionRatePrev),
            ],
            'hasData' => $this->hasData($collectedRevenue, $expenses, $debt),
            'rangeLabel' => $from->toDateString() . ' -> ' . $until->toDateString(),
        ];
    }

    private function hasData(float $revenue, float $expenses, float $debt): bool
    {
        return $revenue > 0
            || $expenses > 0
            || $debt > 0;
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
