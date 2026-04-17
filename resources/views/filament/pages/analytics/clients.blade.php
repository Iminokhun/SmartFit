<x-filament::page>
    @php
        $formatDate  = fn ($value) => \Carbon\Carbon::parse($value)->format('d.m.Y');
        $formatMoney = fn ($value) => number_format((float) $value, 0, '.', ' ');
    @endphp

    <div class="analytics-subscriptions space-y-6">
        <x-filament::section
            heading="Filters"
            icon="heroicon-o-funnel"
            :description="$formatDate($from) . ' — ' . $formatDate($until)"
            collapsible
            collapsed
            class="analytics-panel analytics-filters relative z-20 overflow-visible"
        >
            {{ $this->form }}
        </x-filament::section>

        <x-filament::section heading="KPI" class="analytics-panel analytics-kpi">
            <div class="analytics-grid analytics-grid--2 analytics-grid--4">
                @foreach ($cards as $card)
                    @php
                        $isGood = match($card['sentiment']) {
                            'positive' => $card['direction'] === 'up',
                            'negative' => $card['direction'] === 'down',
                            default    => true,
                        };
                        $accentMod = 'analytics-kpi-accent--' . $card['sentiment'];
                        $badgeMod  = $isGood ? 'analytics-kpi-badge--good' : 'analytics-kpi-badge--bad';
                    @endphp
                    <div class="analytics-kpi-card">
                        <div class="analytics-kpi-accent {{ $accentMod }}"></div>
                        <div>
                            <p class="analytics-kpi-label">{{ $card['label'] }}</p>
                            <p class="analytics-kpi-value">
                                {{ $card['value'] }}@if(!empty($card['suffix']))<span class="analytics-kpi-value-suffix">{{ $card['suffix'] }}</span>@endif
                            </p>
                        </div>
                        @if(!empty($card['delta']))
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
        </x-filament::section>

        <x-filament::section heading="Client Health" class="analytics-panel">
            <div class="analytics-health-bar">
                <div class="analytics-health-bar-segment analytics-health-bar-segment--paid" style="width: {{ round(($clientHealth['paid'] / $clientHealthTotal) * 100, 2) }}%"></div>
                <div class="analytics-health-bar-segment analytics-health-bar-segment--partial" style="width: {{ round(($clientHealth['partial'] / $clientHealthTotal) * 100, 2) }}%"></div>
                <div class="analytics-health-bar-segment analytics-health-bar-segment--unpaid" style="width: {{ round(($clientHealth['unpaid'] / $clientHealthTotal) * 100, 2) }}%"></div>
            </div>
            <div class="analytics-grid analytics-grid--2 analytics-grid--4">
                @php
                    $healthCards = [
                        ['label' => 'Paid Subscriptions',    'value' => number_format((int) $clientHealth['paid']),    'suffix' => null,     'sentiment' => 'positive'],
                        ['label' => 'Partial Subscriptions', 'value' => number_format((int) $clientHealth['partial']), 'suffix' => null,     'sentiment' => 'neutral'],
                        ['label' => 'Unpaid Subscriptions',  'value' => number_format((int) $clientHealth['unpaid']),  'suffix' => null,     'sentiment' => 'negative'],
                        ['label' => 'Total Debt',            'value' => $formatMoney($clientHealth['debt']),           'suffix' => ' UZS',   'sentiment' => 'negative'],
                    ];
                @endphp
                @foreach ($healthCards as $hc)
                    <div class="analytics-kpi-card">
                        <div class="analytics-kpi-accent analytics-kpi-accent--{{ $hc['sentiment'] }}"></div>
                        <div>
                            <p class="analytics-kpi-label">{{ $hc['label'] }}</p>
                            <p class="analytics-kpi-value">
                                {{ $hc['value'] }}@if(!empty($hc['suffix']))<span class="analytics-kpi-value-suffix">{{ $hc['suffix'] }}</span>@endif
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section heading="New vs Active Clients" class="analytics-panel analytics-chart">
            <div class="analytics-note mb-3 text-xs text-gray-500">
                New = customers created in selected period. Active = customers with active subscription overlap or any payment in selected period.
            </div>
            @if ($hasData)
                <div class="analytics-chart-wrap">
                    @livewire('App\\Filament\\Widgets\\Analytics\\ClientsNewActiveChart', [
                        'from'       => $from,
                        'until'      => $until,
                        'activityId' => $activityId,
                    ], key('apex-clients-new-active-' . ($from ?? 'na') . '-' . ($until ?? 'na') . '-' . implode(',', (array) ($activityId ?? []))))
                </div>
            @else
                <div class="analytics-empty">
                    <div class="analytics-empty-title">No data for selected filters</div>
                    <div class="analytics-empty-subtitle">Try broader dates or reset filters to default monthly view.</div>
                    <x-filament::button size="sm" color="gray" wire:click="resetFilters">
                        Reset filters
                    </x-filament::button>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament::page>
