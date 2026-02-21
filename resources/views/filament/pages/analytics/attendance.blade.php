<x-filament::page>
    @php
        $cards = [
            ['label' => 'Total Visits', 'value' => $metrics['totalVisits'], 'type' => 'count'],
            ['label' => 'Attendance Rate', 'value' => $metrics['attendanceRate'], 'type' => 'percent'],
            ['label' => 'Missed Rate', 'value' => $metrics['missedRate'], 'type' => 'percent'],
            ['label' => 'Cancelled Rate', 'value' => $metrics['cancelledRate'], 'type' => 'percent'],
            ['label' => 'No-show Count', 'value' => $metrics['noShowCount'], 'type' => 'count'],
        ];

        $formatDate = fn ($value) => \Carbon\Carbon::parse($value)->format('d.m.Y');
        $fillRateColor = function (float $fillRate): string {
            if ($fillRate >= 75) {
                return 'success';
            }

            if ($fillRate >= 50) {
                return 'warning';
            }

            return 'danger';
        };
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
                    <div class="analytics-label text-sm text-gray-700">Trainer</div>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="trainerId">
                            <option value="">All</option>
                            @foreach ($trainers as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <div class="analytics-label text-sm text-gray-700">Hall</div>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="hallId">
                            <option value="">All</option>
                            @foreach ($halls as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </x-filament::input.select>
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
                    <div class="analytics-label text-sm text-gray-700">Status</div>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="status">
                            <option value="">All</option>
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <div class="analytics-label text-sm text-gray-700">Day of week</div>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="dayOfWeek">
                            <option value="">All</option>
                            @foreach ($dayOptions as $value => $label)
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
            <div class="analytics-grid analytics-grid--2">
                @foreach ($cards as $card)
                    <div class="analytics-kpi-card rounded-xl p-4">
                        <div class="analytics-kpi-label text-xs uppercase tracking-wide text-gray-500">{{ $card['label'] }}</div>
                        <div class="analytics-kpi-value mt-2 text-2xl font-semibold text-gray-900">
                            @if ($card['type'] === 'percent')
                                {{ number_format((float) $card['value'], 1) }}%
                            @else
                                {{ number_format((int) $card['value']) }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        @if ($hasData)
            <div class="analytics-charts grid grid-cols-1 gap-6 xl:grid-cols-2">
                <x-filament::section heading="Attendance Comparison" class="analytics-panel analytics-chart">
                    <div class="analytics-note mb-3 text-xs text-gray-500">
                        Grouped bars make daily visited, missed, and cancelled values easier to compare.
                    </div>
                    <div class="analytics-chart-wrap">
                        @livewire('App\\Filament\\Widgets\\Analytics\\AttendanceVisitsTrendChart', [
                            'from' => $from,
                            'until' => $until,
                            'trainerId' => $trainerId,
                            'hallId' => $hallId,
                            'activityId' => $activityId,
                            'status' => $status,
                            'dayOfWeek' => $dayOfWeek,
                        ], key('attendance-trend-' . ($from ?? 'na') . '-' . ($until ?? 'na') . '-' . ($trainerId ?? 'all') . '-' . ($hallId ?? 'all') . '-' . ($activityId ?? 'all') . '-' . ($status ?? 'all') . '-' . ($dayOfWeek ?? 'all')))
                    </div>
                </x-filament::section>

                <x-filament::section heading="Status Split" class="analytics-panel analytics-chart">
                    <div class="analytics-note mb-3 text-xs text-gray-500">
                        Share of visited, missed, and cancelled statuses.
                    </div>
                    <div class="analytics-chart-wrap">
                        @livewire('App\\Filament\\Widgets\\Analytics\\AttendanceStatusSplitChart', [
                            'from' => $from,
                            'until' => $until,
                            'trainerId' => $trainerId,
                            'hallId' => $hallId,
                            'activityId' => $activityId,
                            'status' => $status,
                            'dayOfWeek' => $dayOfWeek,
                        ], key('attendance-split-' . ($from ?? 'na') . '-' . ($until ?? 'na') . '-' . ($trainerId ?? 'all') . '-' . ($hallId ?? 'all') . '-' . ($activityId ?? 'all') . '-' . ($status ?? 'all') . '-' . ($dayOfWeek ?? 'all')))
                    </div>
                </x-filament::section>
            </div>

            <div class="analytics-charts grid grid-cols-1 gap-6 xl:grid-cols-2">
                <x-filament::section heading="Top Trainers" class="analytics-panel analytics-chart">
                    <div class="analytics-note mb-3 text-xs text-gray-500">
                        Trainers with highest hall fill rate based on visited participants.
                    </div>
                    <div class="analytics-table-wrap overflow-x-auto">
                        <table class="analytics-table min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="analytics-table-head bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">Trainer</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-600">Sessions</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-600">Visited</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-600">Fill rate</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($topTrainers as $row)
                                    <tr>
                                        <td class="px-3 py-2 text-gray-900">{{ $row->trainer_name }}</td>
                                        <td class="px-3 py-2 text-right text-gray-900">{{ number_format((int) $row->sessions_count) }}</td>
                                        <td class="px-3 py-2 text-right text-gray-900">{{ number_format((int) $row->total_visited) }}</td>
                                        <td class="px-3 py-2 text-right text-gray-900">
                                            <x-filament::badge :color="$fillRateColor((float) $row->fill_rate)">
                                                {{ number_format((float) $row->fill_rate, 1) }}%
                                            </x-filament::badge>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-6 text-center text-gray-500">No trainer data in selected period</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>

                <x-filament::section heading="Lowest Trainers" class="analytics-panel analytics-chart">
                    <div class="analytics-note mb-3 text-xs text-gray-500">
                        Trainers with lowest hall fill rate for selected filters.
                    </div>
                    <div class="analytics-table-wrap overflow-x-auto">
                        <table class="analytics-table min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="analytics-table-head bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">Trainer</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-600">Sessions</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-600">Visited</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-600">Fill rate</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($lowestTrainers as $row)
                                    <tr>
                                        <td class="px-3 py-2 text-gray-900">{{ $row->trainer_name }}</td>
                                        <td class="px-3 py-2 text-right text-gray-900">{{ number_format((int) $row->sessions_count) }}</td>
                                        <td class="px-3 py-2 text-right text-gray-900">{{ number_format((int) $row->total_visited) }}</td>
                                        <td class="px-3 py-2 text-right text-gray-900">
                                            <x-filament::badge :color="$fillRateColor((float) $row->fill_rate)">
                                                {{ number_format((float) $row->fill_rate, 1) }}%
                                            </x-filament::badge>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-6 text-center text-gray-500">No trainer data in selected period</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            </div>
        @else
            <x-filament::section heading="No Data" class="analytics-panel analytics-chart">
                <div class="analytics-empty">
                    <div class="analytics-empty-title">No attendance data for selected filters</div>
                    <div class="analytics-empty-subtitle">Try wider dates or reset filters to default monthly view.</div>
                    <x-filament::button size="sm" color="gray" wire:click="resetFilters">
                        Reset filters
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament::page>
