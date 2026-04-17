<x-filament::page>
    @php
        $formatMoney  = fn ($v) => number_format((float) $v, 0, '.', ' ');
        $formatDate   = fn ($v) => \Carbon\Carbon::parse($v)->format('d.m.Y');
        $fromKey      = \Carbon\Carbon::parse($from)->toDateString();
        $untilKey     = \Carbon\Carbon::parse($until)->toDateString();

        // ── KPI cards ──────────────────────────────────────────────────────────
        $buildCard = function (string $key, string $label, string $sentiment, string $valueStr, ?string $suffix = null) use ($kpiDeltas) {
            $d = $kpiDeltas[$key] ?? ['direction' => 'flat', 'percent' => 0.0];
            return [
                'key'        => $key,
                'label'      => $label,
                'sentiment'  => $sentiment,
                'value'      => $valueStr,
                'suffix'     => $suffix,
                'direction'  => $d['direction'],
                'delta'      => $d['direction'] !== 'flat' ? $d['percent'] . '%' : null,
                'deltaLabel' => 'vs prev period',
            ];
        };

        $cards = [
            $buildCard('revenue',        'Revenue',         'positive', $formatMoney($metrics['revenue']),     ' UZS'),
            $buildCard('expenses',       'Expenses',        'negative', $formatMoney($metrics['expenses']),    ' UZS'),
            $buildCard('netProfit',      'Net Profit',      'positive', $formatMoney($metrics['netProfit']),   ' UZS'),
            $buildCard('collectionRate', 'Collection Rate', 'positive', number_format((float) $metrics['collectionRate'], 1), '%'),
            $buildCard('newCustomers',   'New Customers',   'positive', number_format((int) $metrics['newCustomers'])),
            $buildCard('activeClients',  'Active Clients',  'neutral',  number_format((int) $metrics['activeClients'])),
            $buildCard('debt',           'Debt (AR)',        'negative', $formatMoney($metrics['debt']),        ' UZS'),
        ];

        // ── Quick Navigation ───────────────────────────────────────────────────
        $navLinks = [
            ['label' => 'Finance',       'desc' => 'Revenue · Expenses · Profit', 'url' => \App\Filament\Pages\Analytics\Finance::getUrl(),       'color' => 'emerald'],
            ['label' => 'Subscriptions', 'desc' => 'Plans · ARPU · Top Plans',    'url' => \App\Filament\Pages\Analytics\Subscriptions::getUrl(), 'color' => 'blue'],
            ['label' => 'Clients',       'desc' => 'New · Active · ARPU',         'url' => \App\Filament\Pages\Analytics\Clients::getUrl(),       'color' => 'violet'],
            ['label' => 'Attendance',    'desc' => 'Visits · Fill Rate · Peaks',  'url' => \App\Filament\Pages\Analytics\Attendance::getUrl(),    'color' => 'rose'],
            ['label' => 'Retention',     'desc' => 'Churn · LTV · Trends',        'url' => \App\Filament\Pages\Analytics\Retention::getUrl(),     'color' => 'amber'],
            ['label' => 'Forecast',      'desc' => 'Next Month · Collection',     'url' => \App\Filament\Pages\Analytics\Forecast::getUrl(),      'color' => 'sky'],
        ];

        // ── Retention health ───────────────────────────────────────────────────
        $rh            = $retentionHealth;
        $retainedPct   = $rh['total'] > 0 ? round($rh['retained'] / $rh['total'] * 100, 1) : 0.0;

        // ── Drilldown URLs ─────────────────────────────────────────────────────
        $drillRevenue     = \App\Filament\Resources\Payments\PaymentResource::getUrl('index', [
            'tableFilters' => ['status' => ['values' => ['paid', 'partial']], 'created_at' => ['from' => $fromKey, 'until' => $untilKey]],
        ]);
        $drillNewCustomers = \App\Filament\Resources\Customers\CustomerResource::getUrl('index', [
            'tableFilters' => ['created_at' => ['from' => $fromKey, 'until' => $untilKey]],
        ]);
        $drillActive      = \App\Filament\Resources\CustomerSubscriptions\CustomerSubscriptionResource::getUrl('index', [
            'tableFilters' => ['status' => ['values' => ['active']], 'date_range' => ['from' => $fromKey, 'until' => $untilKey]],
        ]);
        $drillDebt        = \App\Filament\Resources\CustomerSubscriptions\CustomerSubscriptionResource::getUrl('index', [
            'tableFilters' => ['debt_open' => ['isActive' => true], 'date_range' => ['from' => $fromKey, 'until' => $untilKey]],
        ]);
        $drillExpenses    = \App\Filament\Resources\Expenses\ExpenseResource::getUrl('index', [
            'tableFilters' => ['expenses_date' => ['from' => $fromKey, 'until' => $untilKey]],
        ]);

        $drilldowns = [
            'revenue'        => $drillRevenue,
            'expenses'       => $drillExpenses,
            'netProfit'      => $drillRevenue,
            'collectionRate' => $drillRevenue,
            'newCustomers'   => $drillNewCustomers,
            'activeClients'  => $drillActive,
            'debt'           => $drillDebt,
        ];
    @endphp

    <div class="overview-layout">

        {{-- ── Period Filter ─────────────────────────────────────────────────── --}}
        <div class="overview-period-bar">
            @foreach (['today' => 'Today', 'week' => 'Week', 'month' => 'Month'] as $val => $lbl)
                <button
                    wire:click="$set('period', '{{ $val }}')"
                    class="overview-period-btn {{ $period === $val ? 'overview-period-btn--active' : '' }}"
                >{{ $lbl }}</button>
            @endforeach
            <span class="overview-period-range">
                {{ $formatDate($from) }} — {{ $formatDate($until) }}
            </span>
        </div>

        {{-- ── KPI Section ───────────────────────────────────────────────────── --}}
        @php
            $renderKpiCard = function (array $card, array $drilldowns): string {
                // rendered inline below — just split array here
                return '';
            };
            $row1 = array_slice($cards, 0, 4); // Revenue, Expenses, Net Profit, Collection Rate
            $row2 = array_slice($cards, 4);    // New Customers, Active Clients, Debt
        @endphp
        <x-filament::section heading="KPI" class="analytics-panel analytics-kpi">
            @foreach ([$row1, $row2] as $row)
                <div class="analytics-grid analytics-grid--4 {{ !$loop->first ? 'mt-4' : '' }}">
                    @foreach ($row as $card)
                        @php
                            $isGood    = match ($card['sentiment']) {
                                'positive' => $card['direction'] === 'up',
                                'negative' => $card['direction'] === 'down',
                                default    => true,
                            };
                            $accentMod = 'analytics-kpi-accent--' . $card['sentiment'];
                            $badgeMod  = $isGood ? 'analytics-kpi-badge--good' : 'analytics-kpi-badge--bad';
                            $drill     = $drilldowns[$card['key']] ?? '#';
                        @endphp
                        <div class="analytics-kpi-card">
                            <div class="analytics-kpi-accent {{ $accentMod }}"></div>
                            <div>
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:0.25rem;">
                                    <p class="analytics-kpi-label">{{ $card['label'] }}</p>
                                    <a href="{{ $drill }}" class="overview-drilldown-link" style="font-size:0.7rem;">↗</a>
                                </div>
                                <p class="analytics-kpi-value">
                                    {{ $card['value'] }}@if(!empty($card['suffix']))<span class="analytics-kpi-value-suffix">{{ $card['suffix'] }}</span>@endif
                                </p>
                            </div>
                            @if (!empty($card['delta']))
                                <div class="analytics-kpi-delta-row">
                                    <span class="analytics-kpi-badge {{ $badgeMod }}">
                                        {{ $card['direction'] === 'up' ? '↑' : '↓' }} {{ $card['delta'] }}
                                    </span>
                                    <span class="analytics-kpi-delta-label">{{ $card['deltaLabel'] }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </x-filament::section>

        {{-- ── Quick Navigation ─────────────────────────────────────────────── --}}
        <x-filament::section heading="Analytics" class="analytics-panel">
            <div class="overview-nav-grid">
                @foreach ($navLinks as $nav)
                    <a href="{{ $nav['url'] }}" class="overview-nav-card overview-nav-card--{{ $nav['color'] }}">
                        <div class="overview-nav-card-body">
                            <div class="overview-nav-card-label">{{ $nav['label'] }}</div>
                            <div class="overview-nav-card-desc">{{ $nav['desc'] }}</div>
                        </div>
                        <div class="overview-nav-card-arrow">→</div>
                    </a>
                @endforeach
            </div>
        </x-filament::section>

        {{-- ── Retention Health + Attendance Status ─────────────────────────── --}}
        <div class="overview-two-grid">

            <x-filament::section heading="Retention Health" class="analytics-panel">
                @if ($rh['total'] > 0)
                    <div class="overview-rh-metrics">
                        <div class="overview-rh-stat">
                            <div class="overview-status-label">Churn Rate</div>
                            <div class="overview-rh-churn">{{ number_format($rh['churnRate'], 1) }}%</div>
                        </div>
                        <div class="overview-rh-stat">
                            <div class="overview-status-label">Retained</div>
                            <div class="overview-rh-value overview-rh-value--good">{{ number_format($rh['retained']) }}</div>
                        </div>
                        <div class="overview-rh-stat">
                            <div class="overview-status-label">Churned</div>
                            <div class="overview-rh-value overview-rh-value--bad">{{ number_format($rh['churned']) }}</div>
                        </div>
                    </div>
                    <div class="trainer-bar-track mt-4">
                        <div class="trainer-bar-fill trainer-bar-fill--emerald" style="width: {{ $retainedPct }}%;"></div>
                    </div>
                    <div class="overview-rh-bar-labels">
                        <span>Retained {{ number_format($retainedPct, 1) }}%</span>
                        <span>Churned {{ number_format(100 - $retainedPct, 1) }}%</span>
                    </div>
                @else
                    <p class="analytics-note">No subscriptions expiring in this period.</p>
                @endif
            </x-filament::section>

            <x-filament::section heading="Attendance Status — last 7 days" class="analytics-panel">
                @php
                    $ss = $statusSummary;
                    $totalAtt = ($ss['visited'] ?? 0) + ($ss['missed'] ?? 0) + ($ss['cancelled'] ?? 0);
                    $visitedPct   = $totalAtt > 0 ? round($ss['visited']   / $totalAtt * 100, 1) : 0;
                    $missedPct    = $totalAtt > 0 ? round($ss['missed']    / $totalAtt * 100, 1) : 0;
                    $cancelledPct = $totalAtt > 0 ? round($ss['cancelled'] / $totalAtt * 100, 1) : 0;
                @endphp
                <div class="overview-att-grid">
                    <div>
                        <div class="overview-status-label">Visited</div>
                        <div class="overview-rh-value overview-rh-value--good">{{ number_format($ss['visited'] ?? 0) }}</div>
                        <div class="trainer-bar-track mt-2">
                            <div class="trainer-bar-fill trainer-bar-fill--emerald" style="width: {{ $visitedPct }}%;"></div>
                        </div>
                        <div class="overview-att-pct">{{ $visitedPct }}%</div>
                    </div>
                    <div>
                        <div class="overview-status-label">Missed</div>
                        <div class="overview-rh-value overview-rh-value--neutral">{{ number_format($ss['missed'] ?? 0) }}</div>
                        <div class="trainer-bar-track mt-2">
                            <div class="trainer-bar-fill trainer-bar-fill--rose" style="width: {{ $missedPct }}%;"></div>
                        </div>
                        <div class="overview-att-pct">{{ $missedPct }}%</div>
                    </div>
                    <div>
                        <div class="overview-status-label">Cancelled</div>
                        <div class="overview-rh-value overview-rh-value--neutral">{{ number_format($ss['cancelled'] ?? 0) }}</div>
                        <div class="trainer-bar-track mt-2">
                            <div class="trainer-bar-fill trainer-bar-fill--blue" style="width: {{ $cancelledPct }}%;"></div>
                        </div>
                        <div class="overview-att-pct">{{ $cancelledPct }}%</div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- ── Charts row 1 ─────────────────────────────────────────────────── --}}
        <div class="overview-two-grid">
            <x-filament::section heading="Revenue per month" class="analytics-panel">
                @livewire('App\\Filament\\Widgets\\Analytics\\OverviewRevenueExpensesMonthlyChart', [
                    'from' => $from,
                    'until' => $until,
                ], key('overview-revenue-exp-monthly-' . $fromKey . '-' . $untilKey))
            </x-filament::section>

            <x-filament::section heading="Net profit per month" class="analytics-panel">
                @livewire('App\\Filament\\Widgets\\Analytics\\OverviewCollectionsMonthlyChart', [
                    'from' => $from,
                    'until' => $until,
                ], key('overview-collections-monthly-' . $fromKey . '-' . $untilKey))
            </x-filament::section>
        </div>

{{-- ── Inventory Snapshot ─────────────────────────────────────────────── --}}
        <x-filament::section class="analytics-panel">
            <x-slot name="heading">
                <div class="overview-card-head" style="width:100%;">
                    <span>Inventory Snapshot</span>
                    <a href="{{ \App\Filament\Resources\Inventories\InventoryResource::getUrl('index') }}" class="overview-drilldown-link">Open inventory →</a>
                </div>
            </x-slot>
            @php
                $lowStockCount            = (int) ($inventorySnapshot['lowStockCount'] ?? 0);
                $assetsInRepair           = (int) ($inventorySnapshot['assetsInRepair'] ?? 0);
                $writtenOffAssets         = (int) ($inventorySnapshot['writtenOffAssets'] ?? 0);
                $eventsTotal              = (int) ($inventorySnapshot['eventsTotal'] ?? 0);
                $eventsTransferred        = (int) ($inventorySnapshot['eventsTransferred'] ?? 0);
                $eventsSentToRepair       = (int) ($inventorySnapshot['eventsSentToRepair'] ?? 0);
                $eventsReturnedFromRepair = (int) ($inventorySnapshot['eventsReturnedFromRepair'] ?? 0);
                $eventsWrittenOff         = (int) ($inventorySnapshot['eventsWrittenOff'] ?? 0);

                @endphp

            {{-- Risk Cards grid --}}
            <div class="analytics-grid analytics-grid--2 analytics-grid--4">
                <x-inventory-risk-card
                    label="Low stock"
                    :value="number_format($lowStockCount)"
                    bgVar="--risk-low-stock"
                    dotVar="--risk-low-stock-dot"
                    :urgent="$lowStockCount > 0"
                    :status="$lowStockCount > 0 ? $lowStockCount . ' items need restock' : 'All stocked'"
                />
                <x-inventory-risk-card
                    label="In repair"
                    :value="number_format($assetsInRepair)"
                    bgVar="--risk-repair"
                    dotVar="--risk-repair-dot"
                    :urgent="false"
                    :status="$assetsInRepair > 0 ? $assetsInRepair . ' asset(s) unavailable' : 'None in repair'"
                />
                <x-inventory-risk-card
                    label="Written off"
                    :value="number_format($writtenOffAssets)"
                    bgVar="--risk-written-off"
                    dotVar="--risk-written-off-dot"
                    :urgent="false"
                    :status="$writtenOffAssets > 0 ? $writtenOffAssets . ' asset(s) decommissioned' : 'None written off'"
                />
                <x-inventory-risk-card
                    label="Events (period)"
                    :value="number_format($eventsTotal)"
                    bgVar="--risk-events"
                    dotVar="--risk-events-dot"
                    :urgent="false"
                    :status="$eventsTotal > 0 ? $eventsTotal . ' movements recorded' : 'No movements'"
                />
            </div>

            {{-- Events + Pie — two columns --}}
            <div class="overview-two-grid" style="margin-top:1.25rem;">

                {{-- Asset Events Breakdown --}}
                <div>
                    <div class="overview-card-head" style="margin-bottom:0.75rem;">
                        <p class="inv-section-label" style="margin-bottom:0;">Asset Events</p>
                        <a href="{{ \App\Filament\Resources\AssetEvents\AssetEventResource::getUrl('index') }}" class="overview-drilldown-link">Open events →</a>
                    </div>
                    <div class="inv-events-list-new">
                        @php
                            $events = [
                                ['label' => 'Transferred',          'value' => $eventsTransferred],
                                ['label' => 'Sent to repair',       'value' => $eventsSentToRepair],
                                ['label' => 'Returned from repair', 'value' => $eventsReturnedFromRepair],
                                ['label' => 'Written off',          'value' => $eventsWrittenOff],
                            ];
                        @endphp
                        @foreach ($events as $ev)
                            <div class="inv-event-row-new">
                                <span class="inv-event-label-new">{{ $ev['label'] }}</span>
                                <span class="inv-event-value-new">{{ number_format($ev['value']) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Expense Mix pie chart --}}
                <div>
                    <p class="inv-section-label">Expense Mix</p>
                    <p class="analytics-note" style="font-size:0.72rem;margin-bottom:0.5rem;">Assets / Consumable / Retail</p>
                    <div class="analytics-chart-wrap">
                        @livewire('App\\Filament\\Widgets\\Analytics\\OverviewInventoryExpenseCategoryPieChart', [
                            'from' => $from,
                            'until' => $until,
                        ], key('overview-inventory-expense-mix-' . $fromKey . '-' . $untilKey))
                    </div>
                </div>

            </div>
        </x-filament::section>

    </div>
</x-filament::page>
