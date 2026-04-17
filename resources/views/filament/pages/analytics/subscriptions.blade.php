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

            <div class="analytics-note mt-3 text-xs text-gray-500">
                Revenue is calculated from paid and partial payments only. Range: {{ $rangeLabel }}
            </div>
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

        <div class="analytics-charts grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-filament::section heading="Revenue Trend" class="analytics-panel analytics-chart">
                <div class="analytics-chart-wrap">
                    @livewire('App\\Filament\\Widgets\\Analytics\\SubscriptionsRevenueTrendChart', [
                        'from' => $from,
                        'until' => $until,
                        'activityId' => $activityId,
                        'paymentMethod' => $paymentMethod,
                        'paymentStatus' => $paymentStatus,
                    ], key('apex-revenue-' . ($from ?? 'na') . '-' . ($until ?? 'na') . '-' . implode(',', (array) ($activityId ?? [])) . '-' . implode(',', (array) ($paymentMethod ?? [])) . '-' . implode(',', (array) ($paymentStatus ?? []))))
                </div>
            </x-filament::section>

            <x-filament::section heading="Clients vs Subscriptions" class="analytics-panel analytics-chart">
                <div class="analytics-chart-wrap">
                    @livewire('App\\Filament\\Widgets\\Analytics\\SubscriptionsClientsSubscriptionsChart', [
                        'from' => $from,
                        'until' => $until,
                        'activityId' => $activityId,
                        'paymentMethod' => $paymentMethod,
                        'paymentStatus' => $paymentStatus,
                    ], key('apex-clients-' . ($from ?? 'na') . '-' . ($until ?? 'na') . '-' . implode(',', (array) ($activityId ?? [])) . '-' . implode(',', (array) ($paymentMethod ?? [])) . '-' . implode(',', (array) ($paymentStatus ?? []))))
                </div>
            </x-filament::section>
        </div>

        <x-filament::section class="analytics-panel">
            <div class="trainer-section-header">
                <div class="trainer-section-left">
                    <p class="trainer-section-eyebrow">Revenue</p>
                    <h3 class="trainer-section-title">Top Plans</h3>
                </div>
                <span class="trainer-section-badge trainer-section-badge--blue">By Revenue</span>
            </div>

            @if ($topPlans->isNotEmpty())
                @php
                    $chartNames   = $topPlans->pluck('name')->values()->toArray();
                    $chartRevenue = $topPlans->pluck('total')->map(fn($v) => (float) $v)->values()->toArray();
                @endphp
                <div class="analytics-chart-wrap mb-4">
                    @livewire('App\\Filament\\Widgets\\Analytics\\SubscriptionsTopPlansChart', [
                        'chartNames'   => $chartNames,
                        'chartRevenue' => $chartRevenue,
                    ], key('apex-top-plans-' . md5(json_encode($chartNames))))
                </div>

                <div class="trainer-rows">
                    @foreach ($topPlans as $row)
                        @php
                            $words    = preg_split('/[\s\-_]+/', trim($row->name));
                            $initials = strtoupper(substr($words[0] ?? '', 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                        @endphp
                        <div class="trainer-row">
                            <div class="trainer-row-left">
                                <div class="trainer-avatar trainer-avatar--blue">{{ $initials }}</div>
                                <div>
                                    <p class="trainer-name">{{ $row->name }}</p>
                                    <div class="trainer-meta">
                                        <span>{{ $row->activity_name }}</span>
                                        <span class="trainer-meta-dot"></span>
                                        <span><span class="trainer-meta-val">{{ number_format((int) $row->sales) }}</span> Sales</span>
                                        <span class="trainer-meta-dot"></span>
                                        <span>Avg <span class="trainer-meta-val">{{ $formatMoney($row->avg_price) }}</span> UZS</span>
                                    </div>
                                </div>
                            </div>
                            <div class="trainer-row-right">
                                <span class="trainer-fill-rate" style="font-size:1.1rem">
                                    {{ $formatMoney($row->total) }}<span class="trainer-fill-rate-suffix"> UZS</span>
                                </span>
                                <div style="display:flex;align-items:center;gap:0.5rem">
                                    <div class="trainer-bar-track" style="flex:1">
                                        <div class="trainer-bar-fill trainer-bar-fill--blue" style="width: {{ min((float) $row->share, 100) }}%"></div>
                                    </div>
                                    <span style="font-size:0.7rem;color:#64748b;flex-shrink:0;width:2.5rem;text-align:right">{{ $row->share }}%</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="trainer-empty">No data for selected period</div>
            @endif
        </x-filament::section>
    </div>
</x-filament::page>
