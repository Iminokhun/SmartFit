<?php

namespace App\Filament\Pages\Analytics;

use App\Enums\PaymentMethod;
use App\Models\Activity;
use App\Models\CustomerSubscription;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class Finance extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedBanknotes;
    protected static string|null|\UnitEnum $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 4;
    protected static ?string $title = 'Finance Analytics';
    protected static ?string $navigationLabel = 'Finance';
    protected static ?string $slug = 'analytics/finance';

    protected string $view = 'filament.pages.analytics.finance';

    public ?array $data = [];

    public function mount(): void
    {
        $today = Carbon::today();

        $this->form->fill([
            'period'            => 'month',
            'from'              => $today->copy()->startOfMonth()->toDateString(),
            'until'             => $today->copy()->endOfMonth()->toDateString(),
            'activityId'        => [],
            'paymentMethod'     => [],
            'paymentStatus'     => [],
            'expenseCategoryId' => [],
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Grid::make(['default' => 1, 'md' => 2, 'lg' => 4])
                    ->schema([
                        Select::make('period')
                            ->label('Period')
                            ->options([
                                'today' => 'Today',
                                'week'  => 'This week',
                                'month' => 'This month',
                                'range' => 'Custom range',
                            ])
                            ->default('month')
                            ->live()
                            ->afterStateUpdated(fn () => $this->syncPeriodDates()),

                        DatePicker::make('from')
                            ->label('From')
                            ->live()
                            ->disabled(fn () => ($this->data['period'] ?? 'month') !== 'range'),

                        DatePicker::make('until')
                            ->label('Until')
                            ->live()
                            ->disabled(fn () => ($this->data['period'] ?? 'month') !== 'range'),

                        Select::make('activityId')
                            ->label('Activity')
                            ->options(fn () => Activity::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->placeholder('All')
                            ->live(),

                        Select::make('paymentMethod')
                            ->label('Payment method')
                            ->options(fn () => PaymentMethod::options())
                            ->multiple()
                            ->placeholder('All')
                            ->live(),

                        Select::make('paymentStatus')
                            ->label('Payment status')
                            ->options([
                                'paid'    => 'Paid',
                                'partial' => 'Partial',
                                'pending' => 'Pending',
                                'failed'  => 'Failed',
                            ])
                            ->multiple()
                            ->placeholder('Paid + Partial')
                            ->live(),

                        Select::make('expenseCategoryId')
                            ->label('Expense category')
                            ->options(fn () => ExpenseCategory::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->placeholder('All')
                            ->live(),
                    ]),
            ])
            ->statePath('data');
    }

    public function resetFilters(): void
    {
        $today = Carbon::today();

        $this->form->fill([
            'period'            => 'month',
            'from'              => $today->copy()->startOfMonth()->toDateString(),
            'until'             => $today->copy()->endOfMonth()->toDateString(),
            'activityId'        => [],
            'paymentMethod'     => [],
            'paymentStatus'     => [],
            'expenseCategoryId' => [],
        ]);
    }

    protected function getViewData(): array
    {
        $activityId        = !empty($this->data['activityId'])        ? array_map('intval', (array) $this->data['activityId'])        : [];
        $expenseCategoryId = !empty($this->data['expenseCategoryId']) ? array_map('intval', (array) $this->data['expenseCategoryId']) : [];
        $paymentMethod     = !empty($this->data['paymentMethod'])     ? (array) $this->data['paymentMethod']                          : [];
        $paymentStatus     = !empty($this->data['paymentStatus'])     ? (array) $this->data['paymentStatus']                          : [];

        [$from, $until] = $this->resolveDateRange();
        [$prevFrom, $prevUntil] = $this->resolvePreviousDateRange($from, $until);
        $revenueStatuses = $this->resolveRevenueStatuses($paymentStatus);

        $paymentsBase = Payment::query()
            ->whereBetween('created_at', [$from, $until])
            ->when($paymentMethod, fn (Builder $q) => $q->whereIn('method', $paymentMethod))
            ->when($paymentStatus, fn (Builder $q) => $q->whereIn('status', $paymentStatus))
            ->when($activityId, function (Builder $q) use ($activityId) {
                $q->whereHas('customerSubscription.subscription', fn (Builder $s) => $s->whereIn('activity_id', $activityId));
            });

        $paymentsPrevBase = Payment::query()
            ->whereBetween('created_at', [$prevFrom, $prevUntil])
            ->when($paymentMethod, fn (Builder $q) => $q->whereIn('method', $paymentMethod))
            ->when($paymentStatus, fn (Builder $q) => $q->whereIn('status', $paymentStatus))
            ->when($activityId, function (Builder $q) use ($activityId) {
                $q->whereHas('customerSubscription.subscription', fn (Builder $s) => $s->whereIn('activity_id', $activityId));
            });

        $collectedRevenue = $collectedRevenuePrev = 0.0;
        if (! empty($revenueStatuses)) {
            $collectedRevenue     = (clone $paymentsBase)->whereIn('status', $revenueStatuses)->sum('amount');
            $collectedRevenuePrev = (clone $paymentsPrevBase)->whereIn('status', $revenueStatuses)->sum('amount');
        }

        $expensesBase = Expense::query()
            ->whereBetween('expenses_date', [$from->toDateString(), $until->toDateString()])
            ->when($expenseCategoryId, fn (Builder $q) => $q->whereIn('category_id', $expenseCategoryId));

        $expensesPrevBase = Expense::query()
            ->whereBetween('expenses_date', [$prevFrom->toDateString(), $prevUntil->toDateString()])
            ->when($expenseCategoryId, fn (Builder $q) => $q->whereIn('category_id', $expenseCategoryId));

        $expenses     = (float) (clone $expensesBase)->sum('amount');
        $expensesPrev = (float) (clone $expensesPrevBase)->sum('amount');

        $netProfit     = $collectedRevenue - $expenses;
        $netProfitPrev = $collectedRevenuePrev - $expensesPrev;

        $debt = (float) CustomerSubscription::query()
            ->whereDate('start_date', '<=', $until->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->when($activityId, fn (Builder $q) => $q->whereHas('subscription', fn (Builder $s) => $s->whereIn('activity_id', $activityId)))
            ->sum('debt');

        $debtPrev = (float) CustomerSubscription::query()
            ->whereDate('start_date', '<=', $prevUntil->toDateString())
            ->whereDate('end_date', '>=', $prevFrom->toDateString())
            ->when($activityId, fn (Builder $q) => $q->whereHas('subscription', fn (Builder $s) => $s->whereIn('activity_id', $activityId)))
            ->sum('debt');

        $collectionRate     = ($collectedRevenue + $debt) > 0 ? ($collectedRevenue / ($collectedRevenue + $debt)) * 100 : 0.0;
        $collectionRatePrev = ($collectedRevenuePrev + $debtPrev) > 0 ? ($collectedRevenuePrev / ($collectedRevenuePrev + $debtPrev)) * 100 : 0.0;

        $deltaNum = fn($cur, $prev): ?string =>
            $prev > 0 ? abs(round(($cur - $prev) / $prev * 100, 1)) . '%' : null;
        $deltaRate = fn($cur, $prev): ?string =>
            $prev > 0 ? abs(round($cur - $prev, 1)) . '%' : null;
        $dir = fn($cur, $prev): string => $cur >= $prev ? 'up' : 'down';

        $cards = [
            [
                'label'      => 'Revenue',
                'value'      => number_format((float) $collectedRevenue, 0, '.', ' '),
                'suffix'     => ' UZS',
                'delta'      => $deltaNum($collectedRevenue, $collectedRevenuePrev),
                'deltaLabel' => 'vs prev period',
                'direction'  => $dir($collectedRevenue, $collectedRevenuePrev),
                'sentiment'  => 'positive',
            ],
            [
                'label'      => 'Expenses',
                'value'      => number_format((float) $expenses, 0, '.', ' '),
                'suffix'     => ' UZS',
                'delta'      => $deltaNum($expenses, $expensesPrev),
                'deltaLabel' => 'vs prev period',
                'direction'  => $dir($expenses, $expensesPrev),
                'sentiment'  => 'negative',
            ],
            [
                'label'      => 'Net Profit',
                'value'      => number_format((float) $netProfit, 0, '.', ' '),
                'suffix'     => ' UZS',
                'delta'      => $deltaNum(abs($netProfit), abs($netProfitPrev)),
                'deltaLabel' => 'vs prev period',
                'direction'  => $dir($netProfit, $netProfitPrev),
                'sentiment'  => 'positive',
            ],
            [
                'label'      => 'Debt (AR)',
                'value'      => number_format((float) $debt, 0, '.', ' '),
                'suffix'     => ' UZS',
                'delta'      => $deltaNum($debt, $debtPrev),
                'deltaLabel' => 'vs prev period',
                'direction'  => $dir($debt, $debtPrev),
                'sentiment'  => 'negative',
            ],
            [
                'label'      => 'Collection Rate',
                'value'      => number_format(round($collectionRate, 1), 1),
                'suffix'     => '%',
                'delta'      => $deltaRate($collectionRate, $collectionRatePrev),
                'deltaLabel' => 'vs prev period',
                'direction'  => $dir($collectionRate, $collectionRatePrev),
                'sentiment'  => 'positive',
            ],
        ];

        return [
            'cards'             => $cards,
            'hasData'           => $collectedRevenue > 0 || $expenses > 0 || $debt > 0,
            'rangeLabel'        => $from->toDateString() . ' → ' . $until->toDateString(),
            'from'              => $from->toDateString(),
            'until'             => $until->toDateString(),
            'activityId'        => $activityId,
            'paymentMethod'     => $paymentMethod,
            'paymentStatus'     => $paymentStatus,
            'expenseCategoryId' => $expenseCategoryId,
        ];
    }

    private function resolveDateRange(): array
    {
        $from  = !empty($this->data['from'])  ? Carbon::parse($this->data['from'])  : Carbon::today();
        $until = !empty($this->data['until']) ? Carbon::parse($this->data['until']) : Carbon::today();

        if ($from->gt($until)) {
            [$from, $until] = [$until, $from];
        }

        return [$from->startOfDay(), $until->endOfDay()];
    }

    private function resolvePreviousDateRange(Carbon $from, Carbon $until): array
    {
        $days      = $from->diffInDays($until) + 1;
        $prevUntil = $from->copy()->subDay()->endOfDay();
        $prevFrom  = $prevUntil->copy()->subDays($days - 1)->startOfDay();

        return [$prevFrom, $prevUntil];
    }

    private function resolveRevenueStatuses(array $paymentStatus): array
    {
        $allowed = ['paid', 'partial'];
        if (!empty($paymentStatus)) {
            return array_values(array_intersect($allowed, $paymentStatus));
        }

        return $allowed;
    }

    private function syncPeriodDates(): void
    {
        $today  = Carbon::today();
        $period = $this->data['period'] ?? 'month';

        switch ($period) {
            case 'today':
                $this->data['from']  = $today->toDateString();
                $this->data['until'] = $today->toDateString();
                break;
            case 'week':
                $this->data['from']  = $today->copy()->startOfWeek()->toDateString();
                $this->data['until'] = $today->copy()->endOfWeek()->toDateString();
                break;
            case 'range':
                $this->data['from']  ??= $today->toDateString();
                $this->data['until'] ??= $today->toDateString();
                break;
            case 'month':
            default:
                $this->data['from']  = $today->copy()->startOfMonth()->toDateString();
                $this->data['until'] = $today->copy()->endOfMonth()->toDateString();
                break;
        }
    }
}
