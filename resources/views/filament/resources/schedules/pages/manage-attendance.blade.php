@php
    use Filament\Support\Enums\FontWeight;
@endphp

<x-filament::page>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold">
                    Attendance for: {{ $record->activity->name ?? 'Schedule #'.$record->id }}
                </h2>
                <p class="text-sm text-gray-500">
                    Trainer: {{ $record->staff->full_name ?? '-' }},
                    Time: {{ $record->start_time }} - {{ $record->end_time }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <label class="text-sm font-medium">
                    Date:
                    <input
                        type="date"
                        wire:model.live="date"
                        class="fi-input mt-1 block rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    />
                </label>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 text-xs text-gray-600">
            <span class="font-medium">Legend:</span>
            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 font-medium text-green-800">
                Visited
            </span>
            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 font-medium text-red-800">
                Missed
            </span>
            <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 font-medium text-yellow-800">
                Cancelled
            </span>
            <span class="inline-flex items-center rounded-full bg-gray-50 px-2.5 py-0.5 font-medium text-gray-500">
                - No visit
            </span>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="sticky left-0 z-10 bg-gray-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Customer
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Subscription
                        </th>
                        @foreach ($dates as $colDate)
                            @php
                                $colCarbon = \Carbon\Carbon::parse($colDate);
                                $isToday = $colCarbon->isToday();
                                $isWeekend = $colCarbon->isWeekend();
                                $baseClasses = 'px-2 py-3 text-center text-xs font-semibold uppercase tracking-wider';
                                $colorClasses = $isToday
                                    ? ' bg-blue-100 text-blue-800'
                                    : ($isWeekend ? ' bg-gray-100 text-gray-600' : ' text-gray-500');
                            @endphp
                            <th class="{{ $baseClasses . $colorClasses }}">
                                <div class="flex flex-col items-center gap-0.5">
                                    <span>{{ strtoupper($colCarbon->shortEnglishDayName) }}</span>
                                    <span class="text-sm font-semibold">{{ $colCarbon->day }}</span>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="sticky left-0 z-0 bg-white px-4 py-3">
                                <div class="font-medium text-gray-900">
                                    {{ $row['customer_name'] }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ $row['subscription_name'] ?: '-' }}
                            </td>
                            @foreach ($dates as $colDate)
                                @php
                                    $status = $row['statuses'][$colDate] ?? null;
                                    $colors = [
                                        'visited' => 'bg-green-100 text-green-800',
                                        'missed' => 'bg-red-100 text-red-800',
                                        'cancelled' => 'bg-yellow-100 text-yellow-800',
                                    ];
                                @endphp
                                <td
                                    class="px-2 py-3 text-center cursor-pointer hover:bg-gray-50"
                                    wire:click="toggleStatus({{ $row['customer_id'] }}, '{{ $colDate }}')"
                                >
                                    @if ($status)
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium {{ $colors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ ucfirst($status) }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-50 px-2 py-0.5 text-[10px] font-medium text-gray-400">
                                            -
                                        </span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 2 + count($dates) }}" class="px-4 py-6 text-center text-sm text-gray-500">
                                No customers with active subscriptions for this schedule on selected date.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament::page>

