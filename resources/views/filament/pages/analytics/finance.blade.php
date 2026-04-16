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
                Revenue uses only paid and partial payments.
            </div>
        </x-filament::section>

        <x-filament::section heading="Finance KPI" class="analytics-panel analytics-kpi">
            <div class="analytics-grid analytics-grid--2 analytics-grid--5">
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

        @if ($hasData)
            <div class="analytics-charts grid grid-cols-1 gap-6 xl:grid-cols-2">
                <x-filament::section heading="Revenue vs Expenses" class="analytics-panel analytics-chart">
                    <div class="analytics-note mb-3 text-xs text-gray-500">
                        Shows collected revenue and recorded expenses by day in selected period.
                    </div>
                    <div class="analytics-chart-wrap">
                        @livewire('App\\Filament\\Widgets\\Analytics\\FinanceRevenueExpensesTrendChart', [
                            'from'              => $from,
                            'until'             => $until,
                            'activityId'        => $activityId,
                            'paymentMethod'     => $paymentMethod,
                            'paymentStatus'     => $paymentStatus,
                            'expenseCategoryId' => $expenseCategoryId,
                        ], key('finance-rev-exp-' . ($from ?? 'na') . '-' . ($until ?? 'na') . '-' . implode(',', (array) ($activityId ?? [])) . '-' . implode(',', (array) ($paymentMethod ?? [])) . '-' . implode(',', (array) ($paymentStatus ?? [])) . '-' . implode(',', (array) ($expenseCategoryId ?? []))))
                    </div>
                </x-filament::section>

                <x-filament::section heading="Pending vs Failed Trend" class="analytics-panel analytics-chart">
                    <div class="analytics-note mb-3 text-xs text-gray-500">
                        Daily count of pending and failed payments. Spikes may indicate billing issues or client payment problems.
                    </div>
                    <div class="analytics-chart-wrap">
                        @livewire('App\\Filament\\Widgets\\Analytics\\FinancePendingFailedTrendChart', [
                            'from'          => $from,
                            'until'         => $until,
                            'activityId'    => $activityId,
                            'paymentMethod' => $paymentMethod,
                        ], key('finance-pending-failed-' . ($from ?? 'na') . '-' . ($until ?? 'na') . '-' . implode(',', (array) ($activityId ?? [])) . '-' . implode(',', (array) ($paymentMethod ?? []))))
                    </div>
                </x-filament::section>
            </div>

            <x-filament::section heading="Expense by Category" class="analytics-panel analytics-chart">
                <div class="analytics-note mb-3 text-xs text-gray-500">
                    Share of expenses by category in selected period.
                </div>
                <div class="analytics-chart-wrap">
                    @livewire('App\\Filament\\Widgets\\Analytics\\FinanceExpenseCategoryPieChart', [
                        'from'              => $from,
                        'until'             => $until,
                        'expenseCategoryId' => $expenseCategoryId,
                    ], key('finance-expense-category-' . ($from ?? 'na') . '-' . ($until ?? 'na') . '-' . implode(',', (array) ($expenseCategoryId ?? []))))
                </div>
            </x-filament::section>
        @else
            <x-filament::section heading="No Data" class="analytics-panel analytics-chart">
                <div class="analytics-empty">
                    <div class="analytics-empty-title">No finance data for selected filters</div>
                    <div class="analytics-empty-subtitle">Try wider dates or reset filters to default monthly view.</div>
                    <x-filament::button size="sm" color="gray" wire:click="resetFilters">
                        Reset filters
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament::page>
