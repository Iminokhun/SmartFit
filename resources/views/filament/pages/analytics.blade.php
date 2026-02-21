<x-filament::page>
    @php
        $cards = [
            ['key' => 'revenue', 'label' => 'Revenue', 'value' => $metrics['revenue'], 'type' => 'money'],
            ['key' => 'newCustomers', 'label' => 'New customers', 'value' => $metrics['newCustomers'], 'type' => 'count'],
            ['key' => 'activeClients', 'label' => 'Active customers', 'value' => $metrics['activeClients'], 'type' => 'count'],
            ['key' => 'debt', 'label' => 'Debt (AR)', 'value' => $metrics['debt'], 'type' => 'money'],
            ['key' => 'inventoryPurchaseCost', 'label' => 'Inventory purchase cost', 'value' => $metrics['inventoryPurchaseCost'], 'type' => 'money'],
        ];

        $formatMoney = fn ($value) => number_format((float) $value, 2, '.', ' ');
        $fromKey = \Carbon\Carbon::parse($from)->toDateString();
        $untilKey = \Carbon\Carbon::parse($until)->toDateString();

        $drilldownUrl = function (string $key) use ($fromKey, $untilKey) {
            return match ($key) {
                'revenue' => \App\Filament\Resources\Payments\PaymentResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => ['values' => ['paid', 'partial']],
                        'created_at' => ['from' => $fromKey, 'until' => $untilKey],
                    ],
                ]),
                'newCustomers' => \App\Filament\Resources\Customers\CustomerResource::getUrl('index', [
                    'tableFilters' => [
                        'created_at' => ['from' => $fromKey, 'until' => $untilKey],
                    ],
                ]),
                'activeClients' => \App\Filament\Resources\CustomerSubscriptions\CustomerSubscriptionResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => ['values' => ['active']],
                        'date_range' => ['from' => $fromKey, 'until' => $untilKey],
                    ],
                ]),
                'debt' => \App\Filament\Resources\CustomerSubscriptions\CustomerSubscriptionResource::getUrl('index', [
                    'tableFilters' => [
                        'debt_open' => ['isActive' => true],
                        'date_range' => ['from' => $fromKey, 'until' => $untilKey],
                    ],
                ]),
                'inventoryPurchaseCost' => \App\Filament\Resources\Expenses\ExpenseResource::getUrl('index', [
                    'tableFilters' => [
                        'expenses_date' => ['from' => $fromKey, 'until' => $untilKey],
                    ],
                ]),
                default => '#',
            };
        };
    @endphp

    <div class="overview-layout">
        <div class="overview-kpi-grid">
            @foreach ($cards as $card)
                @php
                    $delta = $kpiDeltas[$card['key']] ?? ['direction' => 'flat', 'percent' => 0];
                @endphp
                <div class="overview-card">
                    <div class="overview-card-head">
                        <div class="overview-card-label">{{ $card['label'] }}</div>
                        <a href="{{ $drilldownUrl($card['key']) }}" class="overview-drilldown-link">Open</a>
                    </div>
                    <div class="overview-card-value">
                        @if ($card['type'] === 'money')
                            {{ $formatMoney($card['value']) }}
                        @else
                            {{ number_format((int) $card['value']) }}
                        @endif
                    </div>
                    <div class="overview-card-delta {{ $delta['direction'] === 'up' ? 'overview-card-delta-up' : ($delta['direction'] === 'down' ? 'overview-card-delta-down' : 'overview-card-delta-flat') }}">
                        @if ($delta['direction'] === 'up')
                            +{{ $delta['percent'] }}% increase
                        @elseif ($delta['direction'] === 'down')
                            -{{ $delta['percent'] }}% decrease
                        @else
                            0% change
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="overview-two-grid">
            <div class="overview-panel">
                <div class="overview-panel-title">Attendance Status - last week</div>
                <div class="overview-gauge-wrap">
                    <div class="overview-gauge" style="--value: {{ $statusSummary['visitedPercent'] }};">
                        <div class="overview-gauge-inner">
                            <div class="overview-gauge-label">Visited</div>
                            <div class="overview-gauge-value">{{ number_format($statusSummary['visitedPercent'], 1) }}%</div>
                        </div>
                    </div>
                </div>
                <div class="overview-status-grid">
                    <div>
                        <div class="overview-status-label">Visited</div>
                        <div class="overview-status-value">{{ number_format($statusSummary['visited']) }}</div>
                    </div>
                    <div>
                        <div class="overview-status-label">Missed</div>
                        <div class="overview-status-value">{{ number_format($statusSummary['missed']) }}</div>
                    </div>
                    <div>
                        <div class="overview-status-label">Cancelled</div>
                        <div class="overview-status-value">{{ number_format($statusSummary['cancelled']) }}</div>
                    </div>
                </div>
            </div>

            <div class="overview-panel">
                <div class="overview-panel-title">Revenue per month</div>
                @livewire('App\\Filament\\Widgets\\Analytics\\OverviewRevenueExpensesMonthlyChart', [
                    'from' => $from,
                    'until' => $until,
                ], key('overview-revenue-exp-monthly-' . $fromKey . '-' . $untilKey))
            </div>
        </div>

        <div class="overview-two-grid">
            <div class="overview-panel">
                <div class="overview-panel-title">Collections per month</div>
                @livewire('App\\Filament\\Widgets\\Analytics\\OverviewCollectionsMonthlyChart', [
                    'from' => $from,
                    'until' => $until,
                ], key('overview-collections-monthly-' . $fromKey . '-' . $untilKey))
            </div>

            <div class="overview-panel">
                <div class="overview-panel-title">Customer mix (period)</div>
                @livewire('App\\Filament\\Widgets\\Analytics\\OverviewCustomerMixChart', [
                    'from' => $from,
                    'until' => $until,
                ], key('overview-customer-mix-' . $fromKey . '-' . $untilKey))
            </div>
        </div>

        <div class="overview-panel">
            <div class="overview-card-head">
                <div class="overview-panel-title">Inventory Snapshot</div>
                <a href="{{ \App\Filament\Resources\Inventories\InventoryResource::getUrl('index') }}" class="overview-drilldown-link">Open inventory</a>
            </div>
            <div class="overview-panel-note">Current stock risks and asset state for the selected period.</div>
            @php
                $lowStockCount = (int) ($inventorySnapshot['lowStockCount'] ?? 0);
                $assetsInRepair = (int) ($inventorySnapshot['assetsInRepair'] ?? 0);
                $writtenOffAssets = (int) ($inventorySnapshot['writtenOffAssets'] ?? 0);
                $eventsTotal = (int) ($inventorySnapshot['eventsTotal'] ?? 0);
                $eventsTransferred = (int) ($inventorySnapshot['eventsTransferred'] ?? 0);
                $eventsSentToRepair = (int) ($inventorySnapshot['eventsSentToRepair'] ?? 0);
                $eventsReturnedFromRepair = (int) ($inventorySnapshot['eventsReturnedFromRepair'] ?? 0);
                $eventsWrittenOff = (int) ($inventorySnapshot['eventsWrittenOff'] ?? 0);
            @endphp

            <div class="overview-mini-grid">
                <div class="overview-mini-card">
                    <div class="overview-status-label">Low stock items (<= 10)</div>
                    <div class="overview-status-value {{ $lowStockCount > 0 ? 'overview-status-value-danger' : 'overview-status-value-ok' }}">{{ number_format($lowStockCount) }}</div>
                </div>
                <div class="overview-mini-card">
                    <div class="overview-status-label">Assets in repair</div>
                    <div class="overview-status-value {{ $assetsInRepair > 0 ? 'overview-status-value-warn' : 'overview-status-value-ok' }}">{{ number_format($assetsInRepair) }}</div>
                </div>
                <div class="overview-mini-card">
                    <div class="overview-status-label">Written off assets</div>
                    <div class="overview-status-value {{ $writtenOffAssets > 0 ? 'overview-status-value-danger' : 'overview-status-value-ok' }}">{{ number_format($writtenOffAssets) }}</div>
                </div>
                <div class="overview-mini-card">
                    <div class="overview-status-label">Asset events (period)</div>
                    <div class="overview-status-value overview-status-value-neutral">{{ number_format($eventsTotal) }}</div>
                </div>
            </div>

            <div class="overview-card-head mt-4">
                <div class="overview-panel-title">Asset Events Breakdown</div>
                <a href="{{ \App\Filament\Resources\AssetEvents\AssetEventResource::getUrl('index') }}" class="overview-drilldown-link">Open events</a>
            </div>

            <div class="overview-mini-grid">
                <div class="overview-mini-card">
                    <div class="overview-status-label">Transferred</div>
                    <div class="overview-status-value overview-status-value-neutral">{{ number_format($eventsTransferred) }}</div>
                </div>
                <div class="overview-mini-card">
                    <div class="overview-status-label">Sent to repair</div>
                    <div class="overview-status-value {{ $eventsSentToRepair > 0 ? 'overview-status-value-warn' : 'overview-status-value-neutral' }}">{{ number_format($eventsSentToRepair) }}</div>
                </div>
                <div class="overview-mini-card">
                    <div class="overview-status-label">Returned from repair</div>
                    <div class="overview-status-value overview-status-value-ok">{{ number_format($eventsReturnedFromRepair) }}</div>
                </div>
                <div class="overview-mini-card">
                    <div class="overview-status-label">Written off</div>
                    <div class="overview-status-value {{ $eventsWrittenOff > 0 ? 'overview-status-value-danger' : 'overview-status-value-neutral' }}">{{ number_format($eventsWrittenOff) }}</div>
                </div>
            </div>

            <div class="overview-card-head mt-4">
                <div class="overview-panel-title">Inventory Expense Mix</div>
            </div>
            <div class="overview-panel-note">Distribution of inventory purchase expenses by category (Assets / Consumable / Retail).</div>
            <div class="analytics-chart-wrap">
                @livewire('App\\Filament\\Widgets\\Analytics\\OverviewInventoryExpenseCategoryPieChart', [
                    'from' => $from,
                    'until' => $until,
                ], key('overview-inventory-expense-mix-' . $fromKey . '-' . $untilKey))
            </div>
        </div>
    </div>
</x-filament::page>
