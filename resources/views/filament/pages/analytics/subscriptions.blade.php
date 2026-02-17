<x-filament::page>
    @php
        $cards = [
            ['label' => 'Revenue', 'value' => $metrics['revenue']],
            ['label' => 'New subscriptions', 'value' => $metrics['newSubscriptions']],
            ['label' => 'Active subscriptions', 'value' => $metrics['activeSubscriptions']],
            ['label' => 'ARPU', 'value' => $metrics['arpu']],
        ];

        $formatMoney = fn ($value) => number_format((float) $value, 2);
    @endphp

    <div class="analytics-subscriptions space-y-6">
        <x-filament::section heading="Filters" class="analytics-panel analytics-filters">
            <div class="analytics-grid analytics-grid--2">
                <div>
                    <div class="analytics-label text-sm font-medium text-gray-700">Period</div>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="period">
                            @foreach ($periodOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <div class="analytics-label text-sm font-medium text-gray-700">From</div>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="date"
                            wire:model.live="from"
                            :disabled="$period !== 'range'"
                        />
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <div class="analytics-label text-sm font-medium text-gray-700">Until</div>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="date"
                            wire:model.live="until"
                            :disabled="$period !== 'range'"
                        />
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <div class="analytics-label text-sm font-medium text-gray-700">Activity</div>
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
                    <div class="analytics-label text-sm font-medium text-gray-700">Payment method</div>
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
                    <div class="analytics-label text-sm font-medium text-gray-700">Payment status</div>
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
                Revenue is calculated from paid and partial payments only. <br> Range: {{ $rangeLabel }}
            </div>
        </x-filament::section>

        <x-filament::section heading="KPI" class="analytics-panel analytics-kpi">
            <div class="analytics-note mb-3 text-xs text-gray-500">
                Period: {{ $rangeLabel }}
            </div>
            <div class="analytics-grid analytics-grid--2">
                @foreach ($cards as $card)
                    <div class="analytics-kpi-card rounded-xl p-4">
                        <div class="analytics-kpi-label text-xs uppercase tracking-wide text-gray-500">
                            {{ $card['label'] }}
                        </div>
                        <div class="analytics-kpi-value mt-2 text-2xl font-semibold text-gray-900">
                            @if (in_array($card['label'], ['Revenue', 'ARPU'], true))
                                {{ $formatMoney($card['value']) }} UZS
                            @else
                                {{ number_format((int) $card['value']) }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <div class="analytics-charts grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-filament::section heading="Revenue Trend" class="analytics-panel analytics-chart">
                <div class="analytics-chart-wrap">
                    @livewire(\App\Filament\Widgets\Analytics\SubscriptionsRevenueTrendChart::class, [
                        'from' => $from,
                        'until' => $until,
                        'activityId' => $activityId,
                        'paymentMethod' => $paymentMethod,
                        'paymentStatus' => $paymentStatus,
                    ], key('apex-revenue-' . ($from ?? 'na') . '-' . ($until ?? 'na') . '-' . ($activityId ?? 'all') . '-' . ($paymentMethod ?? 'all') . '-' . ($paymentStatus ?? 'all')))
                </div>
            </x-filament::section>

            <x-filament::section heading="Clients vs Subscriptions" class="analytics-panel analytics-chart">
                <div class="analytics-chart-wrap">
                    @livewire(\App\Filament\Widgets\Analytics\SubscriptionsClientsSubscriptionsChart::class, [
                        'from' => $from,
                        'until' => $until,
                        'activityId' => $activityId,
                        'paymentMethod' => $paymentMethod,
                        'paymentStatus' => $paymentStatus,
                    ], key('apex-clients-' . ($from ?? 'na') . '-' . ($until ?? 'na') . '-' . ($activityId ?? 'all') . '-' . ($paymentMethod ?? 'all') . '-' . ($paymentStatus ?? 'all')))
                </div>
            </x-filament::section>
        </div>

        <x-filament::section heading="Top Plans" class="analytics-panel analytics-chart">
            <div class="analytics-chart-wrap">
                @livewire(\App\Filament\Widgets\Analytics\SubscriptionsTopPlansChart::class, [
                    'from' => $from,
                    'until' => $until,
                    'activityId' => $activityId,
                    'paymentMethod' => $paymentMethod,
                    'paymentStatus' => $paymentStatus,
                ], key('apex-top-plans-' . ($from ?? 'na') . '-' . ($until ?? 'na') . '-' . ($activityId ?? 'all') . '-' . ($paymentMethod ?? 'all') . '-' . ($paymentStatus ?? 'all')))
            </div>

            <div class="analytics-table-wrap overflow-x-auto">
                <table class="analytics-table min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="analytics-table-head bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Subscription</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-600">Sales</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-600">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($topPlans as $row)
                            <tr>
                                <td class="px-3 py-2 text-gray-900">{{ $row->name }}</td>
                                <td class="px-3 py-2 text-right text-gray-900">{{ number_format($row->sales) }}</td>
                                <td class="px-3 py-2 text-right text-gray-900">
                                    {{ $formatMoney($row->total) }} UZS
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-6 text-center text-gray-500">No data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament::page>
