<?php

namespace App\Filament\Pages;

use App\Enums\AssetEventType;
use App\Enums\InventoryItemType;
use App\Enums\InventoryStatus;
use App\Models\AssetEvent;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Inventory;
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
        $inventoryPurchaseCost = $this->inventoryPurchaseCost($from, $until);
        $inventoryPurchaseCostPrev = $this->inventoryPurchaseCost($prevFrom, $prevUntil);

        $lowStockCount = Inventory::query()
            ->whereIn('item_type', [InventoryItemType::Consumable->value, InventoryItemType::Retail->value])
            ->where('quantity', '<=', 10)
            ->count();

        $assetsInRepair = Inventory::query()
            ->where('item_type', InventoryItemType::Asset->value)
            ->where('status', InventoryStatus::Repair->value)
            ->count();

        $writtenOffAssets = Inventory::query()
            ->where('item_type', InventoryItemType::Asset->value)
            ->where('status', InventoryStatus::WrittenOff->value)
            ->count();

        $eventRows = AssetEvent::query()
            ->selectRaw('event_type, COUNT(*) as total')
            ->whereBetween('event_date', [$from, $until])
            ->groupBy('event_type')
            ->get()
            ->keyBy('event_type');

        return [
            'from' => $from,
            'until' => $until,
            'metrics' => [
                'revenue' => $revenue,
                'newCustomers' => $newCustomers,
                'activeClients' => $activeClients,
                'debt' => $debt,
                'inventoryPurchaseCost' => $inventoryPurchaseCost,
            ],
            'kpiDeltas' => [
                'revenue' => KpiCalculator::delta($revenue, $revenuePrev),
                'newCustomers' => KpiCalculator::delta($newCustomers, $newCustomersPrev),
                'activeClients' => KpiCalculator::delta($activeClients, $activeClientsPrev),
                'debt' => KpiCalculator::delta($debt, $debtPrev),
                'inventoryPurchaseCost' => KpiCalculator::delta($inventoryPurchaseCost, $inventoryPurchaseCostPrev),
            ],
            'statusSummary' => $statusKpi->statusSummary(),
            'inventorySnapshot' => [
                'lowStockCount' => $lowStockCount,
                'assetsInRepair' => $assetsInRepair,
                'writtenOffAssets' => $writtenOffAssets,
                'eventsTotal' => (int) $eventRows->sum('total'),
                'eventsTransferred' => (int) ($eventRows[AssetEventType::Transferred->value]->total ?? 0),
                'eventsSentToRepair' => (int) ($eventRows[AssetEventType::SentToRepair->value]->total ?? 0),
                'eventsReturnedFromRepair' => (int) ($eventRows[AssetEventType::ReturnedFromRepair->value]->total ?? 0),
                'eventsWrittenOff' => (int) ($eventRows[AssetEventType::WrittenOff->value]->total ?? 0),
            ],
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

    private function inventoryPurchaseCost(Carbon $from, Carbon $until): float
    {
        $categoryIds = $this->inventoryExpenseCategoryIds();

        if ($categoryIds === []) {
            return 0.0;
        }

        return (float) Expense::query()
            ->whereIn('category_id', $categoryIds)
            ->whereBetween('expenses_date', [$from->toDateString(), $until->toDateString()])
            ->sum('amount');
    }

    private function inventoryExpenseCategoryIds(): array
    {
        static $cachedIds = null;

        if ($cachedIds !== null) {
            return $cachedIds;
        }

        $allowed = ['asset', 'assets', 'consumable', 'consumables', 'retail', 'retails'];

        $cachedIds = ExpenseCategory::query()
            ->get(['id', 'name'])
            ->filter(function (ExpenseCategory $category) use ($allowed) {
                $name = str($category->name)->lower()->replace(['_', '-'], ' ')->squish()->value();
                return in_array($name, $allowed, true);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return $cachedIds;
    }

    private function syncPeriodDates(): void
    {
        $today = Carbon::today();
        $this->from = $today->copy()->startOfMonth()->toDateString();
        $this->until = $today->copy()->endOfMonth()->toDateString();
    }
}
