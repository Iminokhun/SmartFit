<x-filament::page>
    @php
        $formatMoney   = fn ($value) => number_format((float) $value, 0, '.', ' ');
        $daysLeftColor = function (int $days): string {
            if ($days > 14) return 'success';
            if ($days > 7)  return 'warning';
            return 'danger';
        };
        $renderCards = function (array $cards, string $gridClass) {
            return ['cards' => $cards, 'gridClass' => $gridClass];
        };
    @endphp

    <div class="analytics-subscriptions space-y-6">

        {{-- Filters --}}
        <x-filament::section
            heading="Filters"
            icon="heroicon-o-funnel"
            collapsible
            collapsed
            class="analytics-panel analytics-filters relative z-20 overflow-visible"
        >
            {{ $this->form }}
            <div class="analytics-note mt-3 text-xs text-gray-500">
                Forecast is based on already contracted (active &amp; pending) subscriptions. Each subscription's agreed price is counted once, in the month it starts.
            </div>
        </x-filament::section>

        {{-- This Month KPI --}}
        <x-filament::section heading="This Month" class="analytics-panel analytics-kpi">
            <div class="analytics-grid analytics-grid--2 analytics-grid--3">
                @foreach ($thisMonthCards as $card)
                    @php
                        $isGood    = match($card['sentiment']) {
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
                            @if(!empty($card['hint']))
                                <p class="analytics-kpi-hint">{{ $card['hint'] }}</p>
                            @endif
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

        {{-- Outlook KPI --}}
        <x-filament::section heading="Outlook" class="analytics-panel analytics-kpi">
            <div class="analytics-grid analytics-grid--2 analytics-grid--4">
                @foreach ($outlookCards as $card)
                    @php
                        $isGood    = match($card['sentiment']) {
                            'positive' => $card['direction'] === 'up',
                            'negative' => $card['direction'] === 'down',
                            default    => true,
                        };
                        $accentMod = 'analytics-kpi-accent--' . $card['sentiment'];
                    @endphp
                    <div class="analytics-kpi-card">
                        <div class="analytics-kpi-accent {{ $accentMod }}"></div>
                        <div>
                            <p class="analytics-kpi-label">{{ $card['label'] }}</p>
                            <p class="analytics-kpi-value">
                                {{ $card['value'] }}@if(!empty($card['suffix']))<span class="analytics-kpi-value-suffix">{{ $card['suffix'] }}</span>@endif
                            </p>
                            @if(!empty($card['hint']))
                                <p class="analytics-kpi-hint">{{ $card['hint'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Charts --}}
        <div class="analytics-charts grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-filament::section heading="Collection Efficiency" class="analytics-panel analytics-chart">
                <div class="analytics-note mb-3 text-xs text-gray-500">
                    Purple line = collection rate % (Collected ÷ Contracted). Bars show absolute values in millions UZS. Dashed line = 80% target.
                </div>
                <div class="analytics-chart-wrap">
                    @livewire('App\\Filament\\Widgets\\Analytics\\ForecastCollectionTrendChart', [
                        'activityIds' => $activityIds,
                    ], key('forecast-collection-' . implode(',', $activityIds ?? [])))
                </div>
            </x-filament::section>

            <x-filament::section heading="Revenue at Risk by Month" class="analytics-panel analytics-chart">

                @if ($hasData)
                    {{-- Per-month at-risk breakdown --}}
                    <div class="analytics-grid analytics-grid--2" style="margin-bottom:1rem;">
                        @foreach ($riskMonths as $rm)
                            <div class="inv-risk-card-new" style="background:{{ $rm['risk_count'] > 0 ? 'hsl(0 70% 97%)' : 'hsl(160 40% 96%)' }}; border-color: {{ $rm['risk_count'] > 0 ? '#fecaca' : '#bbf7d0' }};">
                                <div class="inv-risk-card-header">
                                    <span class="inv-risk-card-label">
                                        {{ $rm['label'] }}
                                        @if($rm['is_current']) <span style="font-size:0.6rem;color:#6b7280;">(current)</span> @endif
                                    </span>
                                    @if($rm['risk_count'] > 0)
                                        <a href="{{ $rm['drill_url'] }}" class="overview-drilldown-link">View list →</a>
                                    @endif
                                </div>
                                <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:0.5rem;">
                                    <div>
                                        <div class="inv-risk-card-value">{{ $rm['total_count'] }}</div>
                                        <div class="inv-risk-card-status">subscriptions expiring</div>
                                    </div>
                                    <div style="text-align:right;">
                                        @if($rm['risk_count'] > 0)
                                            <div style="font-size:0.8rem;font-weight:700;color:#dc2626;">{{ $rm['risk_count'] }} at risk</div>
                                            <div style="font-size:0.7rem;color:#6b7280;">{{ number_format($rm['risk_amount'], 0, '.', ' ') }} UZS debt</div>
                                        @else
                                            <div style="font-size:0.8rem;font-weight:700;color:#16a34a;">All clean</div>
                                        @endif
                                        @if($rm['clean_count'] > 0)
                                            <div style="font-size:0.7rem;color:#6b7280;">{{ $rm['clean_count'] }} paid up</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Chart --}}
                    <div class="analytics-note" style="font-size:0.72rem;margin-bottom:0.5rem;">
                        Green = no debt · Red = has outstanding debt · Value in millions UZS
                    </div>
                    <div class="analytics-chart-wrap">
                        @livewire('App\\Filament\\Widgets\\Analytics\\ForecastExpiringValueChart', [
                            'activityIds' => $activityIds,
                        ], key('forecast-expiring-' . implode(',', $activityIds ?? [])))
                    </div>
                @else
                    <div class="analytics-empty">
                        <div class="analytics-empty-title">No data available</div>
                        <div class="analytics-empty-subtitle">No active or pending subscriptions found.</div>
                        <x-filament::button size="sm" color="gray" wire:click="resetFilters">Reset filters</x-filament::button>
                    </div>
                @endif
            </x-filament::section>
        </div>


    </div>
</x-filament::page>
