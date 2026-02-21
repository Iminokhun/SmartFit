<x-filament::page>
    @php
        $cards = [
            ['key' => 'revenue', 'label' => 'Revenue', 'value' => $metrics['revenue']],
            ['key' => 'expenses', 'label' => 'Expenses', 'value' => $metrics['expenses']],
            ['key' => 'netProfit', 'label' => 'Net Profit', 'value' => $metrics['netProfit']],
            ['key' => 'debt', 'label' => 'Debt (AR)', 'value' => $metrics['debt']],
            ['key' => 'collectionRate', 'label' => 'Collection Rate', 'value' => $metrics['collectionRate'], 'type' => 'percent'],
        ];

        $formatMoney = fn ($value) => number_format((float) $value, 2, '.', ' ');
        $formatDate = fn ($value) => \Carbon\Carbon::parse($value)->format('d.m.Y');
    @endphp

    <div class="analytics-subscriptions space-y-6">
        <x-filament::section heading="Filters" class="analytics-panel analytics-filters">
            <div class="analytics-grid analytics-grid--2">
                <div>
                    <div class="analytics-label text-sm text-gray-700">Period</div>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="period">
                            @foreach ($periodOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <div class="analytics-label text-sm text-gray-700">From</div>
                    <x-filament::input.wrapper>
                        <x-filament::input type="date" wire:model.live="from" :disabled="$period !== 'range'" />
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <div class="analytics-label text-sm text-gray-700">Until</div>
                    <x-filament::input.wrapper>
                        <x-filament::input type="date" wire:model.live="until" :disabled="$period !== 'range'" />
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <div class="analytics-label text-sm text-gray-700">Activity</div>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="activityId">
                            <option value="">All</option>
                            @foreach ($activities as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <div class="analytics-label text-sm text-gray-700">Payment method</div>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="paymentMethod">
                            <option value="">All</option>
                            @foreach ($paymentMethods as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <div class="analytics-label text-sm text-gray-700">Payment status</div>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="paymentStatus">
                            <option value="">Paid + Partial</option>
                            @foreach ($paymentStatuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <div class="analytics-label text-sm text-gray-700">Expense category</div>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="expenseCategoryId">
                            <option value="">All</option>
                            @foreach ($expenseCategories as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            </div>
            <div class="analytics-note mt-3 text-xs text-gray-500">
                Range: {{ $formatDate($from) }} - {{ $formatDate($until) }}. Revenue uses only paid and partial payments.
            </div>
        </x-filament::section>

        <x-filament::section heading="Finance KPI" class="analytics-panel analytics-kpi">
            <div class="analytics-grid analytics-grid--2">
                @foreach ($cards as $card)
                    <div class="analytics-kpi-card rounded-xl p-4">
                        <div class="analytics-kpi-label text-xs uppercase tracking-wide text-gray-500">{{ $card['label'] }}</div>
                        @php
                            $delta = $kpiDeltas[$card['key']] ?? ['direction' => 'flat', 'percent' => 0];
                        @endphp
                        <div class="analytics-kpi-delta analytics-kpi-delta--{{ $delta['direction'] }}">
                            @if ($delta['direction'] === 'up')
                                ▲ +{{ $delta['percent'] }}%
                            @elseif ($delta['direction'] === 'down')
                                ▼ -{{ $delta['percent'] }}%
                            @else
                                ■ 0%
                            @endif

                        </div>
                        <div class="analytics-kpi-value mt-2 text-2xl font-semibold text-gray-900">
                            @if (($card['type'] ?? null) === 'percent')
                                {{ number_format((float) $card['value'], 1) }}%
                            @else
                                {{ $formatMoney($card['value']) }} UZS
                            @endif
                        </div>
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
                            'from' => $from,
                            'until' => $until,
                            'activityId' => $activityId,
                            'paymentMethod' => $paymentMethod,
                            'paymentStatus' => $paymentStatus,
                            'expenseCategoryId' => $expenseCategoryId,
                        ], key('finance-rev-exp-' . ($from ?? 'na') . '-' . ($until ?? 'na') . '-' . ($activityId ?? 'all') . '-' . ($paymentMethod ?? 'all') . '-' . ($paymentStatus ?? 'all') . '-' . ($expenseCategoryId ?? 'all')))
                    </div>
                </x-filament::section>

                <x-filament::section heading="Collections vs Debt" class="analytics-panel analytics-chart">
                    <div class="analytics-note mb-3 text-xs text-gray-500">
                        Collections are paid/partial payments. Debt is open receivable from active subscriptions.
                    </div>
                    <div class="analytics-chart-wrap">
                        @livewire('App\\Filament\\Widgets\\Analytics\\FinanceCollectionsDebtTrendChart', [
                            'from' => $from,
                            'until' => $until,
                            'activityId' => $activityId,
                            'paymentMethod' => $paymentMethod,
                            'paymentStatus' => $paymentStatus,
                        ], key('finance-col-debt-' . ($from ?? 'na') . '-' . ($until ?? 'na') . '-' . ($activityId ?? 'all') . '-' . ($paymentMethod ?? 'all') . '-' . ($paymentStatus ?? 'all')))
                    </div>
                </x-filament::section>
            </div>

            <div class="analytics-charts grid grid-cols-1 gap-6">
                <x-filament::section heading="Expense by Category" class="analytics-panel analytics-chart">
                    <div class="analytics-note mb-3 text-xs text-gray-500">
                        Share of expenses by category in selected period.
                    </div>
                    <div class="analytics-chart-wrap">
                        @livewire('App\\Filament\\Widgets\\Analytics\\FinanceExpenseCategoryPieChart', [
                            'from' => $from,
                            'until' => $until,
                            'expenseCategoryId' => $expenseCategoryId,
                        ], key('finance-expense-category-' . ($from ?? 'na') . '-' . ($until ?? 'na') . '-' . ($expenseCategoryId ?? 'all')))
                    </div>
                </x-filament::section>
            </div>
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
