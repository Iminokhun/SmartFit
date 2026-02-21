<x-filament::page>
    @php
        $cards = [
            ['key' => 'newClients', 'label' => 'New clients', 'value' => $metrics['newClients'], 'type' => 'count'],
            ['key' => 'activeClients', 'label' => 'Active clients', 'value' => $metrics['activeClients'], 'type' => 'count'],
            ['key' => 'payingClients', 'label' => 'Paying clients', 'value' => $metrics['payingClients'], 'type' => 'count'],
            ['key' => 'arpu', 'label' => 'ARPU', 'value' => $metrics['arpu'], 'type' => 'money'],
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
                        <x-filament::input
                            type="date"
                            wire:model.live="from"
                            :disabled="$period !== 'range'"
                        />
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <div class="analytics-label text-sm text-gray-700">Until</div>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="date"
                            wire:model.live="until"
                            :disabled="$period !== 'range'"
                        />
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
            </div>

            <div class="analytics-note mt-3 text-xs text-gray-500">
                Range: {{ $formatDate($from) }} - {{ $formatDate($until) }}
            </div>
        </x-filament::section>

        <x-filament::section heading="KPI" class="analytics-panel analytics-kpi">
            <div class="analytics-note mb-3 text-xs text-gray-500">
                Revenue for ARPU is calculated from paid and partial payments.
            </div>
            <div class="analytics-grid analytics-grid--2">
                @foreach ($cards as $card)
                    <div class="analytics-kpi-card rounded-xl p-4">
                        <div class="analytics-kpi-label text-xs uppercase tracking-wide text-gray-500">
                            {{ $card['label'] }}
                        </div>
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
                            vs previous period
                        </div>
                        <div class="analytics-kpi-value mt-2 text-2xl font-semibold text-gray-900">
                            @if ($card['type'] === 'money')
                                {{ $formatMoney($card['value']) }} UZS
                            @else
                                {{ number_format((int) $card['value']) }}
                            @endif
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
                        'from' => $from,
                        'until' => $until,
                        'activityId' => $activityId,
                    ], key('apex-clients-new-active-' . ($from ?? 'na') . '-' . ($until ?? 'na') . '-' . ($activityId ?? 'all')))
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

        <x-filament::section heading="Client Health" class="analytics-panel analytics-chart">
            <div class="analytics-health-bar">
                <div class="analytics-health-bar-segment analytics-health-bar-segment--paid" style="width: {{ round(($clientHealth['paid'] / $clientHealthTotal) * 100, 2) }}%"></div>
                <div class="analytics-health-bar-segment analytics-health-bar-segment--partial" style="width: {{ round(($clientHealth['partial'] / $clientHealthTotal) * 100, 2) }}%"></div>
                <div class="analytics-health-bar-segment analytics-health-bar-segment--unpaid" style="width: {{ round(($clientHealth['unpaid'] / $clientHealthTotal) * 100, 2) }}%"></div>
            </div>
            <div class="analytics-grid analytics-grid--2">
                <div class="analytics-kpi-card analytics-kpi-card--paid rounded-xl p-4">
                    <div class="analytics-kpi-label text-xs uppercase tracking-wide text-gray-500">Paid Subscriptions</div>
                    <div class="analytics-kpi-value mt-2 text-2xl font-semibold text-gray-900">
                        {{ number_format((int) $clientHealth['paid']) }}
                    </div>
                </div>
                <div class="analytics-kpi-card analytics-kpi-card--partial rounded-xl p-4">
                    <div class="analytics-kpi-label text-xs uppercase tracking-wide text-gray-500">Partial Subscriptions</div>
                    <div class="analytics-kpi-value mt-2 text-2xl font-semibold text-gray-900">
                        {{ number_format((int) $clientHealth['partial']) }}
                    </div>
                </div>
                <div class="analytics-kpi-card analytics-kpi-card--unpaid rounded-xl p-4">
                    <div class="analytics-kpi-label text-xs uppercase tracking-wide text-gray-500">Unpaid Subscriptions</div>
                    <div class="analytics-kpi-value mt-2 text-2xl font-semibold text-gray-900">
                        {{ number_format((int) $clientHealth['unpaid']) }}
                    </div>
                </div>
                <div class="analytics-kpi-card analytics-kpi-card--debt rounded-xl p-4">
                    <div class="analytics-kpi-label text-xs uppercase tracking-wide text-gray-500">Total Debt</div>
                    <div class="analytics-kpi-value mt-2 text-2xl font-semibold text-gray-900">
                        {{ $formatMoney($clientHealth['debt']) }} UZS
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament::page>
