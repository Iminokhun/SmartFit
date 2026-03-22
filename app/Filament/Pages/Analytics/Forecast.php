<?php

namespace App\Filament\Pages\Analytics;

use App\Filament\Resources\CustomerSubscriptions\CustomerSubscriptionResource;
use App\Models\Activity;
use App\Models\CustomerSubscription;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class Forecast extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedChartBar;
    protected static string|null|\UnitEnum $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 8;
    protected static ?string $title = 'Revenue Forecast';
    protected static ?string $navigationLabel = 'Forecast';
    protected static ?string $slug = 'analytics/forecast';

    protected string $view = 'filament.pages.analytics.forecast';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(['activityIds' => []]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('activityIds')
                    ->label('Activities')
                    ->options(fn () => Activity::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->placeholder('All activities')
                    ->live(),
            ])
            ->statePath('data');
    }

    public function resetFilters(): void
    {
        $this->form->fill(['activityIds' => []]);
    }

    protected function getViewData(): array
    {
        $activityIds = array_values(array_filter(
            array_map('intval', (array) ($this->data['activityIds'] ?? []))
        ));
        $today = Carbon::today();

        $thisMonthStart  = $today->copy()->startOfMonth();
        $thisMonthEnd    = $today->copy()->endOfMonth();
        $prevMonthStart  = $today->copy()->subMonth()->startOfMonth();
        $prevMonthEnd    = $today->copy()->subMonth()->endOfMonth();
        $nextMonthStart  = $today->copy()->addMonth()->startOfMonth();
        $twoMonthsStart  = $today->copy()->addMonths(2)->startOfMonth();

        $collected = (float) Payment::query()
            ->whereIn('status', ['paid', 'partial'])
            ->whereBetween('created_at', [$thisMonthStart, $thisMonthEnd->copy()->endOfDay()])
            ->when($activityIds, fn (Builder $q) => $q->whereHas('customerSubscription.subscription', fn (Builder $s) => $s->whereIn('activity_id', $activityIds)))
            ->sum('amount');

        $collectedPrev = (float) Payment::query()
            ->whereIn('status', ['paid', 'partial'])
            ->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd->copy()->endOfDay()])
            ->when($activityIds, fn (Builder $q) => $q->whereHas('customerSubscription.subscription', fn (Builder $s) => $s->whereIn('activity_id', $activityIds)))
            ->sum('amount');

        $contracted = (float) CustomerSubscription::query()
            ->whereIn('status', ['active', 'pending'])
            ->whereYear('start_date', $thisMonthStart->year)
            ->whereMonth('start_date', $thisMonthStart->month)
            ->when($activityIds, fn (Builder $q) => $q->whereHas('subscription', fn (Builder $s) => $s->whereIn('activity_id', $activityIds)))
            ->sum('agreed_price');

        $contractedPrev = (float) CustomerSubscription::query()
            ->whereIn('status', ['active', 'pending'])
            ->whereYear('start_date', $prevMonthStart->year)
            ->whereMonth('start_date', $prevMonthStart->month)
            ->when($activityIds, fn (Builder $q) => $q->whereHas('subscription', fn (Builder $s) => $s->whereIn('activity_id', $activityIds)))
            ->sum('agreed_price');

        $forecastNext = (float) CustomerSubscription::query()
            ->whereIn('status', ['active', 'pending'])
            ->whereYear('start_date', $nextMonthStart->year)
            ->whereMonth('start_date', $nextMonthStart->month)
            ->when($activityIds, fn (Builder $q) => $q->whereHas('subscription', fn (Builder $s) => $s->whereIn('activity_id', $activityIds)))
            ->sum('agreed_price');

        $forecastTwo = (float) CustomerSubscription::query()
            ->whereIn('status', ['active', 'pending'])
            ->whereYear('start_date', $twoMonthsStart->year)
            ->whereMonth('start_date', $twoMonthsStart->month)
            ->when($activityIds, fn (Builder $q) => $q->whereHas('subscription', fn (Builder $s) => $s->whereIn('activity_id', $activityIds)))
            ->sum('agreed_price');

        $totalDebt = (float) CustomerSubscription::query()
            ->whereIn('status', ['active', 'pending'])
            ->when($activityIds, fn (Builder $q) => $q->whereHas('subscription', fn (Builder $s) => $s->whereIn('activity_id', $activityIds)))
            ->sum('debt');

        $collectionRate     = $contracted > 0 ? round($collected / $contracted * 100, 1) : 0.0;
        $collectionRatePrev = $contractedPrev > 0 ? round($collectedPrev / $contractedPrev * 100, 1) : 0.0;

        $deltaNum  = fn($cur, $prev): ?string => $prev > 0 ? abs(round(($cur - $prev) / $prev * 100, 1)) . '%' : null;
        $deltaRate = fn($cur, $prev): ?string => $prev > 0 ? abs(round($cur - $prev, 1)) . '%' : null;
        $dir       = fn($cur, $prev): string => $cur >= $prev ? 'up' : 'down';

        $thisMonthCards = [
            [
                'label'      => 'Collected',
                'value'      => number_format($collected, 0, '.', ' '),
                'suffix'     => ' UZS',
                'hint'       => 'Actual paid + partial payments',
                'delta'      => $deltaNum($collected, $collectedPrev),
                'deltaLabel' => 'vs last month',
                'direction'  => $dir($collected, $collectedPrev),
                'sentiment'  => 'positive',
            ],
            [
                'label'      => 'Contracted',
                'value'      => number_format($contracted, 0, '.', ' '),
                'suffix'     => ' UZS',
                'hint'       => 'Agreed price of subscriptions starting this month',
                'delta'      => $deltaNum($contracted, $contractedPrev),
                'deltaLabel' => 'vs last month',
                'direction'  => $dir($contracted, $contractedPrev),
                'sentiment'  => 'neutral',
            ],
            [
                'label'      => 'Collection Rate',
                'value'      => number_format($collectionRate, 1),
                'suffix'     => '%',
                'hint'       => 'Collected ÷ Contracted for this month',
                'delta'      => $deltaRate($collectionRate, $collectionRatePrev),
                'deltaLabel' => 'vs last month',
                'direction'  => $dir($collectionRate, $collectionRatePrev),
                'sentiment'  => 'positive',
            ],
        ];

        $outlookCards = [
            [
                'label'      => $nextMonthStart->format('F Y'),
                'value'      => number_format($forecastNext, 0, '.', ' '),
                'suffix'     => ' UZS',
                'hint'       => 'From subscriptions already contracted',
                'delta'      => null,
                'deltaLabel' => null,
                'direction'  => 'up',
                'sentiment'  => 'neutral',
            ],
            [
                'label'      => $twoMonthsStart->format('F Y'),
                'value'      => number_format($forecastTwo, 0, '.', ' '),
                'suffix'     => ' UZS',
                'hint'       => 'From subscriptions already contracted',
                'delta'      => null,
                'deltaLabel' => null,
                'direction'  => 'up',
                'sentiment'  => 'neutral',
            ],
            [
                'label'      => 'Outstanding Debt',
                'value'      => number_format($totalDebt, 0, '.', ' '),
                'suffix'     => ' UZS',
                'hint'       => 'Unpaid amount across active subscriptions',
                'delta'      => null,
                'deltaLabel' => null,
                'direction'  => $totalDebt > 0 ? 'up' : 'down',
                'sentiment'  => 'negative',
            ],
        ];

        // ── At-Risk breakdown per expiry month ────────────────────────────────
        $riskMonths = [];
        for ($i = 0; $i <= 3; $i++) {
            $ms = $today->copy()->addMonths($i)->startOfMonth();
            $me = $ms->copy()->endOfMonth();

            $base = CustomerSubscription::query()
                ->whereIn('status', ['active', 'pending'])
                ->whereDate('end_date', '>=', $ms->toDateString())
                ->whereDate('end_date', '<=', $me->toDateString())
                ->when($activityIds, fn (Builder $q) => $q->whereHas('subscription', fn (Builder $s) => $s->whereIn('activity_id', $activityIds)));

            $totalCount  = (clone $base)->count();
            $riskCount   = (clone $base)->where('debt', '>', 0)->count();
            $riskAmount  = (float) (clone $base)->where('debt', '>', 0)->sum('agreed_price');
            $cleanCount  = $totalCount - $riskCount;

            $riskMonths[] = [
                'label'       => $ms->format('F Y'),
                'is_current'  => $i === 0,
                'total_count' => $totalCount,
                'risk_count'  => $riskCount,
                'clean_count' => $cleanCount,
                'risk_amount' => $riskAmount,
                'drill_url'   => CustomerSubscriptionResource::getUrl('index', [
                    'tableFilters' => [
                        'status'    => ['values' => ['active', 'pending']],
                        'debt_open' => ['isActive' => true],
                        'date_range' => ['until' => $me->toDateString()],
                    ],
                ]),
            ];
        }

        return [
            'thisMonthCards' => $thisMonthCards,
            'outlookCards'   => $outlookCards,
            'hasData'        => $collected > 0 || $contracted > 0 || $forecastNext > 0,
            'activityIds'    => $activityIds,
            'riskMonths'     => $riskMonths,
        ];
    }
}
