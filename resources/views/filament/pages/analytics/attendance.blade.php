<x-filament::page>
    @php
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
            <div class="analytics-grid analytics-grid--2">
                <x-filament::section class="analytics-panel">
                    <div class="trainer-section-header">
                        <div class="trainer-section-left">
                            <p class="trainer-section-eyebrow">Performance</p>
                            <h3 class="trainer-section-title">Top Performing Trainers</h3>
                        </div>
                        <span class="trainer-section-badge trainer-section-badge--emerald">High Fill Rate</span>
                    </div>
                    <div class="trainer-rows">
                        @forelse ($topTrainers as $row)
                            @php
                                $parts    = explode(' ', trim($row->trainer_name));
                                $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
                            @endphp
                            <div class="trainer-row">
                                <div class="trainer-row-left">
                                    <div class="trainer-avatar trainer-avatar--emerald">{{ $initials }}</div>
                                    <div>
                                        <p class="trainer-name">{{ $row->trainer_name }}</p>
                                        <div class="trainer-meta">
                                            <span><span class="trainer-meta-val">{{ number_format((int) $row->sessions_count) }}</span> Sessions</span>
                                            <span class="trainer-meta-dot"></span>
                                            <span><span class="trainer-meta-val">{{ number_format((int) $row->total_visited) }}</span> Visits</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="trainer-row-right">
                                    <span class="trainer-fill-rate">{{ number_format((float) $row->fill_rate, 1) }}<span class="trainer-fill-rate-suffix">%</span></span>
                                    <div class="trainer-bar-track">
                                        <div class="trainer-bar-fill trainer-bar-fill--emerald" style="width: {{ min((float) $row->fill_rate, 100) }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="trainer-empty">No trainer data in selected period</div>
                        @endforelse
                    </div>
                </x-filament::section>

                <x-filament::section class="analytics-panel">
                    <div class="trainer-section-header">
                        <div class="trainer-section-left">
                            <p class="trainer-section-eyebrow">Optimization</p>
                            <h3 class="trainer-section-title">Trainers Needing Attention</h3>
                        </div>
                        <span class="trainer-section-badge trainer-section-badge--rose">Low Fill Rate</span>
                    </div>
                    <div class="trainer-rows">
                        @forelse ($lowestTrainers as $row)
                            @php
                                $parts    = explode(' ', trim($row->trainer_name));
                                $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
                            @endphp
                            <div class="trainer-row">
                                <div class="trainer-row-left">
                                    <div class="trainer-avatar trainer-avatar--rose">{{ $initials }}</div>
                                    <div>
                                        <p class="trainer-name">{{ $row->trainer_name }}</p>
                                        <div class="trainer-meta">
                                            <span><span class="trainer-meta-val">{{ number_format((int) $row->sessions_count) }}</span> Sessions</span>
                                            <span class="trainer-meta-dot"></span>
                                            <span><span class="trainer-meta-val">{{ number_format((int) $row->total_visited) }}</span> Visits</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="trainer-row-right">
                                    <span class="trainer-fill-rate">{{ number_format((float) $row->fill_rate, 1) }}<span class="trainer-fill-rate-suffix">%</span></span>
                                    <div class="trainer-bar-track">
                                        <div class="trainer-bar-fill trainer-bar-fill--rose" style="width: {{ min((float) $row->fill_rate, 100) }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="trainer-empty">No trainer data in selected period</div>
                        @endforelse
                    </div>
                </x-filament::section>
            </div>

            <div class="analytics-charts grid grid-cols-1 gap-6 xl:grid-cols-2">
                <x-filament::section heading="Attendance Comparison" class="analytics-panel analytics-chart">
                    <div class="analytics-note mb-3 text-xs text-gray-500">
                        Grouped bars make daily visited, missed, and cancelled values easier to compare.
                    </div>
                    <div class="analytics-chart-wrap">
                        @livewire(\App\Filament\Widgets\Analytics\AttendanceVisitsTrendChart::class, [
                            'from' => $from,
                            'until' => $until,
                            'trainerId' => $trainerId,
                            'hallId' => $hallId,
                            'activityId' => $activityId,
                            'status' => $status,
                            'dayOfWeek' => $dayOfWeek,
                        ], key('attendance-trend-' . ($from ?? 'na') . '-' . ($until ?? 'na') . '-' . implode(',', (array) ($trainerId ?? [])) . '-' . implode(',', (array) ($hallId ?? [])) . '-' . implode(',', (array) ($activityId ?? [])) . '-' . implode(',', (array) ($status ?? [])) . '-' . implode(',', (array) ($dayOfWeek ?? []))))
                    </div>
                </x-filament::section>

                <x-filament::section heading="Status Split" class="analytics-panel analytics-chart">
                    <div class="analytics-note mb-3 text-xs text-gray-500">
                        Share of visited, missed, and cancelled statuses.
                    </div>
                    <div class="analytics-chart-wrap">
                        @livewire(\App\Filament\Widgets\Analytics\AttendanceStatusSplitChart::class, [
                            'from' => $from,
                            'until' => $until,
                            'trainerId' => $trainerId,
                            'hallId' => $hallId,
                            'activityId' => $activityId,
                            'status' => $status,
                            'dayOfWeek' => $dayOfWeek,
                        ], key('attendance-split-' . ($from ?? 'na') . '-' . ($until ?? 'na') . '-' . implode(',', (array) ($trainerId ?? [])) . '-' . implode(',', (array) ($hallId ?? [])) . '-' . implode(',', (array) ($activityId ?? [])) . '-' . implode(',', (array) ($status ?? [])) . '-' . implode(',', (array) ($dayOfWeek ?? []))))
                    </div>
                </x-filament::section>
            </div>

            <x-filament::section heading="Peak Hours" class="analytics-panel analytics-chart">
                <div class="analytics-note mb-3 text-xs text-gray-500">
                    Check-in distribution by hour and day of week. Peak hour is highlighted in red.
                </div>
                <div class="analytics-chart-wrap">
                    @livewire(\App\Filament\Widgets\Analytics\PeakHoursHeatmapChart::class, [
                        'from'       => $from,
                        'until'      => $until,
                        'activityId' => $activityId,
                        'hallId'     => $hallId,
                    ], key('peak-heatmap-' . ($from ?? 'na') . '-' . ($until ?? 'na') . '-' . implode(',', (array) ($activityId ?? [])) . '-' . implode(',', (array) ($hallId ?? []))))
                </div>
            </x-filament::section>
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
