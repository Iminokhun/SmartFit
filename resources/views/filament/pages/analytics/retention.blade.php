<x-filament::page>
    @php
        $formatDate = fn ($value) => \Carbon\Carbon::parse($value)->format('d.m.Y');
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
            <div class="analytics-note mt-3 text-xs text-gray-500">
                Churned = subscription expired in period AND no active/pending subscription today.
            </div>
        </x-filament::section>

        <x-filament::section heading="KPI" class="analytics-panel analytics-kpi">
            <div class="analytics-grid analytics-grid--2 analytics-grid--4">
                @foreach ($cards as $card)
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

        <x-filament::section heading="Growth vs Churn — Leaky Bucket" class="analytics-panel analytics-chart">
            <div class="analytics-note mb-3 text-xs text-gray-500">
                Green bars = new clients joined. Red bars = clients churned. Purple line = net growth (new − churned). When green &gt; red the business is growing.
            </div>
            <div class="analytics-chart-wrap">
                @livewire('App\\Filament\\Widgets\\Analytics\\RetentionLeakyBucketChart', [
                    'from'        => $from,
                    'until'       => $until,
                    'activityIds' => $activityIds,
                ], key('apex-leaky-bucket-' . ($from ?? 'na') . '-' . ($until ?? 'na') . '-' . implode(',', $activityIds ?? [])))
            </div>
        </x-filament::section>


    </div>
</x-filament::page>
