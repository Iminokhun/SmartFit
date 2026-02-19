<?php

namespace App\Filament\Pages;

use App\Services\Analytics\KpiCalculator;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Analytics extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedChartBar;
    protected static string|null|\UnitEnum $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Analytics Overview';
    protected static ?string $navigationLabel = 'Overview';

    protected string $view = 'filament.pages.analytics';

    public string $period = 'month';
    public ?string $from = null;
    public ?string $until = null;

    public function mount(): void
    {
        $this->syncPeriodDates();
    }

    protected function getViewData(): array
    {
        [$from, $until] = $this->resolveDateRange();
        [$prevFrom, $prevUntil] = $this->resolvePreviousDateRange($from, $until);

        $kpi = new KpiCalculator($from, $until);
        $kpiPrev = new KpiCalculator($prevFrom, $prevUntil);
        $statusFrom = Carbon::today()->subDays(6)->startOfDay();
        $statusUntil = Carbon::today()->endOfDay();
        $statusKpi = new KpiCalculator($statusFrom, $statusUntil);

        $revenue = $kpi->revenue();
        $revenuePrev = $kpiPrev->revenue();

        $newCustomers = $kpi->newCustomers();
        $newCustomersPrev = $kpiPrev->newCustomers();

        $activeClients = $kpi->activeCustomers();
        $activeClientsPrev = $kpiPrev->activeCustomers();

        $debt = $kpi->debt();
        $debtPrev = $kpiPrev->debt();

        return [
            'from' => $from,
            'until' => $until,
            'metrics' => [
                'revenue' => $revenue,
                'newCustomers' => $newCustomers,
                'activeClients' => $activeClients,
                'debt' => $debt,
            ],
            'kpiDeltas' => [
                'revenue' => KpiCalculator::delta($revenue, $revenuePrev),
                'newCustomers' => KpiCalculator::delta($newCustomers, $newCustomersPrev),
                'activeClients' => KpiCalculator::delta($activeClients, $activeClientsPrev),
                'debt' => KpiCalculator::delta($debt, $debtPrev),
            ],
            'statusSummary' => $kpi->statusSummary(),
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

    private function syncPeriodDates(): void
    {
        $today = Carbon::today();
        $this->from = $today->copy()->startOfMonth()->toDateString();
        $this->until = $today->copy()->endOfMonth()->toDateString();
    }
}



