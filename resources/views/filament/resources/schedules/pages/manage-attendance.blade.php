<x-filament::page>
    <div class="attendance">
        <x-filament::section
            :heading="$record->activity->name ?? 'Schedule #' . $record->id"
        >
            <x-slot name="afterHeader">
                <div class="attendance-date">
                    <span class="attendance-date-label">Date</span>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="date"
                            wire:model.live="date"
                        />
                    </x-filament::input.wrapper>
                </div>
            </x-slot>

            <div class="attendance-meta">
                <div>
                    Time:
                    {{ \Carbon\Carbon::parse($record->start_time)->format('H:i') }}
                    -
                    {{ \Carbon\Carbon::parse($record->end_time)->format('H:i') }}
                </div>
                <div>Trainer: {{ $record->staff->full_name ?? '-' }}</div>
            </div>

            <div class="attendance-legend">
                <span class="attendance-legend-label">Legend:</span>
                <x-filament::badge color="success" size="sm">Visited</x-filament::badge>
                <x-filament::badge color="danger" size="sm">Missed</x-filament::badge>
                <x-filament::badge color="warning" size="sm">Cancelled</x-filament::badge>
                <x-filament::badge color="gray" size="sm">No visit</x-filament::badge>
            </div>

            <div class="attendance-table-wrap">
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Subscription</th>
                            @foreach ($dates as $colDate)
                                @php
                                    $colCarbon = \Carbon\Carbon::parse($colDate)->locale(app()->getLocale());
                                @endphp
                                <th class="attendance-date-cell">
                                    <div class="attendance-day">{{ strtoupper($colCarbon->translatedFormat('D')) }}</div>
                                    <div class="attendance-day-num">{{ $colCarbon->day }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td class="attendance-col-customer">{{ $row['customer_name'] }}</td>
                                <td>{{ $row['subscription_name'] ?: '-' }}</td>
                                @foreach ($dates as $colDate)
                                    @php
                                        $status = $row['statuses'][$colDate] ?? null;
                                        $statusColors = [
                                            'visited' => 'success',
                                            'missed' => 'danger',
                                            'cancelled' => 'warning',
                                        ];
                                    @endphp
                                    <td class="attendance-col-center">
                                        <button
                                            type="button"
                                            class="attendance-status-btn"
                                            wire:click="toggleStatus({{ $row['customer_id'] }}, '{{ $colDate }}')"
                                        >
                                            @if ($status)
                                                <x-filament::badge :color="$statusColors[$status] ?? 'gray'" size="xs">
                                                    {{ ucfirst($status) }}
                                                </x-filament::badge>
                                            @else
                                                <x-filament::badge color="gray" size="xs">-</x-filament::badge>
                                            @endif
                                        </button>
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 2 + count($dates) }}" class="attendance-empty">
                                    No customers with active subscriptions for this schedule on selected date.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament::page>
