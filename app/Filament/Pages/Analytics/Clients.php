<?php

namespace App\Filament\Pages\Analytics;

use App\Enums\PaymentMethod;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\CustomerSubscription;
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

class Clients extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedUsers;
    protected static string|null|\UnitEnum $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 3;
    protected static ?string $title = 'Client Analytics';
    protected static ?string $navigationLabel = 'Clients';
    protected static ?string $slug = 'analytics/clients';

    protected string $view = 'filament.pages.analytics.clients';

    public ?array $data = [];

    public function mount(): void
    {
        $today = Carbon::today();

        $this->form->fill([
            'period'        => 'month',
            'from'          => $today->copy()->startOfMonth()->toDateString(),
            'until'         => $today->copy()->endOfMonth()->toDateString(),
            'activityId'    => [],
            'paymentMethod' => [],
            'paymentStatus' => [],
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Grid::make(['default' => 1, 'md' => 2, 'lg' => 3])
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
                    ]),
            ])
            ->statePath('data');
    }

    public function resetFilters(): void
    {
        $today = Carbon::today();

        $this->form->fill([
            'period'        => 'month',
            'from'          => $today->copy()->startOfMonth()->toDateString(),
            'until'         => $today->copy()->endOfMonth()->toDateString(),
            'activityId'    => [],
            'paymentMethod' => [],
            'paymentStatus' => [],
        ]);
    }

    protected function getViewData(): array
    {
        $activityId    = !empty($this->data['activityId'])    ? array_map('intval', (array) $this->data['activityId']) : [];
        $paymentMethod = !empty($this->data['paymentMethod']) ? (array) $this->data['paymentMethod']                    : [];
        $paymentStatus = !empty($this->data['paymentStatus']) ? (array) $this->data['paymentStatus']                    : [];

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

        $newClients     = Customer::query()->whereBetween('created_at', [$from, $until])->count();
        $newClientsPrev = Customer::query()->whereBetween('created_at', [$prevFrom, $prevUntil])->count();

        $activeBySubscription = CustomerSubscription::query()
            ->select('customer_id')
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $until->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->when($activityId, fn (Builder $q) => $q->whereHas('subscription', fn (Builder $s) => $s->whereIn('activity_id', $activityId)))
            ->distinct()->pluck('customer_id');

        $activeBySubscriptionPrev = CustomerSubscription::query()
            ->select('customer_id')
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $prevUntil->toDateString())
            ->whereDate('end_date', '>=', $prevFrom->toDateString())
            ->when($activityId, fn (Builder $q) => $q->whereHas('subscription', fn (Builder $s) => $s->whereIn('activity_id', $activityId)))
            ->distinct()->pluck('customer_id');

        $activeByPayment     = (clone $paymentsBase)->select('customer_id')->distinct()->pluck('customer_id');
        $activeByPaymentPrev = (clone $paymentsPrevBase)->select('customer_id')->distinct()->pluck('customer_id');

        $activeClients     = $activeBySubscription->merge($activeByPayment)->unique()->count();
        $activeClientsPrev = $activeBySubscriptionPrev->merge($activeByPaymentPrev)->unique()->count();

        $revenue = $revenuePrev = 0.0;
        $payingClients = $payingClientsPrev = 0;

        if (! empty($revenueStatuses)) {
            $revenue           = (clone $paymentsBase)->whereIn('status', $revenueStatuses)->sum('amount');
            $revenuePrev       = (clone $paymentsPrevBase)->whereIn('status', $revenueStatuses)->sum('amount');
            $payingClients     = (clone $paymentsBase)->whereIn('status', $revenueStatuses)->distinct('customer_id')->count('customer_id');
            $payingClientsPrev = (clone $paymentsPrevBase)->whereIn('status', $revenueStatuses)->distinct('customer_id')->count('customer_id');
        }

        $arpu     = $payingClients > 0 ? $revenue / $payingClients : 0;
        $arpuPrev = $payingClientsPrev > 0 ? $revenuePrev / $payingClientsPrev : 0;

        $subscriptionsWindow = CustomerSubscription::query()
            ->whereDate('start_date', '<=', $until->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->when($activityId, fn (Builder $q) => $q->whereHas('subscription', fn (Builder $s) => $s->whereIn('activity_id', $activityId)));

        $clientHealth = [
            'paid'    => (clone $subscriptionsWindow)->where('payment_status', 'paid')->count(),
            'partial' => (clone $subscriptionsWindow)->where('payment_status', 'partial')->count(),
            'unpaid'  => (clone $subscriptionsWindow)->where('payment_status', 'unpaid')->count(),
            'debt'    => (float) (clone $subscriptionsWindow)->sum('debt'),
        ];
        $clientHealthTotal = max(1, $clientHealth['paid'] + $clientHealth['partial'] + $clientHealth['unpaid']);
        $hasData = ($newClients + $activeClients + $payingClients) > 0 || $clientHealth['debt'] > 0;

        $deltaNum = fn($cur, $prev): ?string =>
            $prev > 0 ? abs(round(($cur - $prev) / $prev * 100, 1)) . '%' : null;
        $dir = fn($cur, $prev): string => $cur >= $prev ? 'up' : 'down';

        $cards = [
            [
                'label'      => 'New Clients',
                'value'      => $newClients,
                'suffix'     => null,
                'delta'      => $deltaNum($newClients, $newClientsPrev),
                'deltaLabel' => 'vs prev period',
                'direction'  => $dir($newClients, $newClientsPrev),
                'sentiment'  => 'positive',
            ],
            [
                'label'      => 'Active Clients',
                'value'      => $activeClients,
                'suffix'     => null,
                'delta'      => $deltaNum($activeClients, $activeClientsPrev),
                'deltaLabel' => 'vs prev period',
                'direction'  => $dir($activeClients, $activeClientsPrev),
                'sentiment'  => 'neutral',
            ],
            [
                'label'      => 'Paying Clients',
                'value'      => $payingClients,
                'suffix'     => null,
                'delta'      => $deltaNum($payingClients, $payingClientsPrev),
                'deltaLabel' => 'vs prev period',
                'direction'  => $dir($payingClients, $payingClientsPrev),
                'sentiment'  => 'positive',
            ],
            [
                'label'      => 'ARPU',
                'value'      => number_format((float) $arpu, 0, '.', ' '),
                'suffix'     => ' UZS',
                'delta'      => $deltaNum($arpu, $arpuPrev),
                'deltaLabel' => 'vs prev period',
                'direction'  => $dir($arpu, $arpuPrev),
                'sentiment'  => 'positive',
            ],
        ];

        return [
            'cards'             => $cards,
            'clientHealth'      => $clientHealth,
            'clientHealthTotal' => $clientHealthTotal,
            'hasData'           => $hasData,
            'rangeLabel'        => $from->toDateString() . ' → ' . $until->toDateString(),
            'from'              => $from->toDateString(),
            'until'             => $until->toDateString(),
            'activityId'        => $activityId,
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
