<?php

namespace App\Filament\Pages\Analytics;

use App\Models\Activity;
use App\Models\Customer;
use App\Models\CustomerSubscription;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Retention extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedArrowPath;
    protected static string|null|\UnitEnum $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 6;
    protected static ?string $title = 'Retention & Churn';
    protected static ?string $navigationLabel = 'Retention';
    protected static ?string $slug = 'analytics/retention';

    protected string $view = 'filament.pages.analytics.retention';

    public ?array $data = [];

    public function mount(): void
    {
        $today = Carbon::today();

        $this->form->fill([
            'period'      => '6months',
            'from'        => $today->copy()->subMonths(5)->startOfMonth()->toDateString(),
            'until'       => $today->copy()->endOfMonth()->toDateString(),
            'activityIds' => [],
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
                                'month'   => 'This month',
                                '3months' => 'Last 3 months',
                                '6months' => 'Last 6 months',
                                'year'    => 'This year',
                                'range'   => 'Custom range',
                            ])
                            ->default('6months')
                            ->live()
                            ->afterStateUpdated(fn () => $this->syncPeriodDates()),

                        DatePicker::make('from')
                            ->label('From')
                            ->live()
                            ->disabled(fn () => ($this->data['period'] ?? '6months') !== 'range'),

                        DatePicker::make('until')
                            ->label('Until')
                            ->live()
                            ->disabled(fn () => ($this->data['period'] ?? '6months') !== 'range'),

                        Select::make('activityIds')
                            ->label('Activities')
                            ->options(fn () => Activity::query()->orderBy('name')->pluck('name', 'id')->all())
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
            'period'      => '6months',
            'from'        => $today->copy()->subMonths(5)->startOfMonth()->toDateString(),
            'until'       => $today->copy()->endOfMonth()->toDateString(),
            'activityIds' => [],
        ]);
    }

    protected function getViewData(): array
    {
        $activityIds = array_values(array_filter(
            array_map('intval', (array) ($this->data['activityIds'] ?? []))
        ));

        [$from, $until] = $this->resolveDateRange();
        [$prevFrom, $prevUntil] = $this->resolvePreviousDateRange($from, $until);

        [$churned, $retained, $churnRate, , $avgLtvDays] = $this->calcMetrics($from, $until, $activityIds);
        [$churnedPrev, $retainedPrev, $churnRatePrev]    = $this->calcMetrics($prevFrom, $prevUntil, $activityIds);

        $hasData = ($churned + $retained) > 0;

        $deltaNum  = fn($cur, $prev): ?string => $prev > 0 ? abs(round(($cur - $prev) / $prev * 100, 1)) . '%' : null;
        $deltaRate = fn($cur, $prev): ?string => $prev > 0 ? abs(round($cur - $prev, 1)) . '%' : null;
        $dir       = fn($cur, $prev): string => $cur >= $prev ? 'up' : 'down';

        $cards = [
            [
                'label'      => 'Churned',
                'value'      => $churned,
                'suffix'     => null,
                'delta'      => $deltaNum($churned, $churnedPrev),
                'deltaLabel' => 'vs prev period',
                'direction'  => $dir($churned, $churnedPrev),
                'sentiment'  => 'negative',
            ],
            [
                'label'      => 'Churn Rate',
                'value'      => number_format($churnRate, 1),
                'suffix'     => '%',
                'delta'      => $deltaRate($churnRate, $churnRatePrev),
                'deltaLabel' => 'vs prev period',
                'direction'  => $dir($churnRate, $churnRatePrev),
                'sentiment'  => 'negative',
            ],
            [
                'label'      => 'Retained',
                'value'      => $retained,
                'suffix'     => null,
                'delta'      => $deltaNum($retained, $retainedPrev),
                'deltaLabel' => 'vs prev period',
                'direction'  => $dir($retained, $retainedPrev),
                'sentiment'  => 'positive',
            ],
            [
                'label'      => 'Avg LTV',
                'value'      => number_format(round($avgLtvDays, 0)),
                'suffix'     => ' days',
                'delta'      => null,
                'deltaLabel' => null,
                'direction'  => 'up',
                'sentiment'  => 'neutral',
            ],
        ];

        return [
            'cards'       => $cards,
            'hasData'     => $hasData,
            'rangeLabel'  => $from->toDateString() . ' → ' . $until->toDateString(),
            'from'        => $this->data['from'] ?? $from->toDateString(),
            'until'       => $this->data['until'] ?? $until->toDateString(),
            'activityIds' => $activityIds,
        ];
    }

    private function calcMetrics(Carbon $from, Carbon $until, array $activityIds): array
    {
        $expiringIds = CustomerSubscription::query()
            ->whereBetween('end_date', [$from->toDateString(), $until->toDateString()])
            ->when($activityIds, fn ($q) => $q->whereHas('subscription', fn ($s) => $s->whereIn('activity_id', $activityIds)))
            ->distinct()
            ->pluck('customer_id');

        if ($expiringIds->isEmpty()) {
            return [0, 0, 0.0, 100.0, 0.0];
        }

        $churnedIds = Customer::query()
            ->whereIn('id', $expiringIds)
            ->whereDoesntHave('subscriptions', fn ($q) =>
                $q->whereIn('status', ['active', 'pending'])
                  ->whereDate('end_date', '>=', today())
            )
            ->pluck('id');

        $churned  = $churnedIds->count();
        $retained = $expiringIds->count() - $churned;
        $total    = $churned + $retained;

        $churnRate   = $total > 0 ? round($churned / $total * 100, 1) : 0.0;
        $renewalRate = $total > 0 ? round($retained / $total * 100, 1) : 100.0;

        $avgLtvDays = 0.0;
        if ($churnedIds->isNotEmpty()) {
            $avgLtvDays = (float) CustomerSubscription::query()
                ->whereIn('customer_id', $churnedIds)
                ->selectRaw('customer_id, MIN(start_date) as first_date, MAX(end_date) as last_date')
                ->groupBy('customer_id')
                ->get()
                ->avg(fn ($r) => Carbon::parse($r->first_date)->diffInDays(Carbon::parse($r->last_date)));
        }

        return [$churned, $retained, $churnRate, $renewalRate, $avgLtvDays];
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

    private function syncPeriodDates(): void
    {
        $today  = Carbon::today();
        $period = $this->data['period'] ?? '6months';

        switch ($period) {
            case 'month':
                $this->data['from']  = $today->copy()->startOfMonth()->toDateString();
                $this->data['until'] = $today->copy()->endOfMonth()->toDateString();
                break;
            case '3months':
                $this->data['from']  = $today->copy()->subMonths(2)->startOfMonth()->toDateString();
                $this->data['until'] = $today->copy()->endOfMonth()->toDateString();
                break;
            case 'year':
                $this->data['from']  = $today->copy()->startOfYear()->toDateString();
                $this->data['until'] = $today->copy()->endOfYear()->toDateString();
                break;
            case 'range':
                $this->data['from']  ??= $today->toDateString();
                $this->data['until'] ??= $today->toDateString();
                break;
            case '6months':
            default:
                $this->data['from']  = $today->copy()->subMonths(5)->startOfMonth()->toDateString();
                $this->data['until'] = $today->copy()->endOfMonth()->toDateString();
                break;
        }
    }
}
