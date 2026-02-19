<x-filament::page>
    @php
        $cards = [
            ['key' => 'revenue', 'label' => 'Revenue', 'value' => $metrics['revenue'], 'type' => 'money'],
            ['key' => 'newCustomers', 'label' => 'New customers', 'value' => $metrics['newCustomers'], 'type' => 'count'],
            ['key' => 'activeClients', 'label' => 'Active customers', 'value' => $metrics['activeClients'], 'type' => 'count'],
            ['key' => 'debt', 'label' => 'Debt (AR)', 'value' => $metrics['debt'], 'type' => 'money'],
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
                default => '#',
            };
        };
    @endphp

    <div class="overview-layout">
        <div class="overview-kpi-grid">
            @foreach ($cards as $card)
                @php($delta = $kpiDeltas[$card['key']] ?? ['direction' => 'flat', 'percent' => 0])
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
                @livewire(\App\Filament\Widgets\Analytics\OverviewRevenueExpensesMonthlyChart::class, [
                    'from' => $from,
                    'until' => $until,
                ], key('overview-revenue-exp-monthly-' . $fromKey . '-' . $untilKey))
            </div>
        </div>

        <div class="overview-two-grid">
            <div class="overview-panel">
                <div class="overview-panel-title">Collections per month</div>
                @livewire(\App\Filament\Widgets\Analytics\OverviewCollectionsMonthlyChart::class, [
                    'from' => $from,
                    'until' => $until,
                ], key('overview-collections-monthly-' . $fromKey . '-' . $untilKey))
            </div>

            <div class="overview-panel">
                <div class="overview-panel-title">Customer mix (period)</div>
                @livewire(\App\Filament\Widgets\Analytics\OverviewCustomerMixChart::class, [
                    'from' => $from,
                    'until' => $until,
                ], key('overview-customer-mix-' . $fromKey . '-' . $untilKey))
            </div>
        </div>
    </div>
</x-filament::page>
