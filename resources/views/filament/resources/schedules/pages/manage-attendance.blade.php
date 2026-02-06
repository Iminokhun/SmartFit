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

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Customer
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Subscription
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Status
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">
                                    {{ $row['customer_name'] }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ $row['subscription_name'] ?: '-' }}
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $status = $row['status'];
                                    $colors = [
                                        'visited' => 'bg-green-100 text-green-800',
                                        'missed' => 'bg-red-100 text-red-800',
                                        'cancelled' => 'bg-yellow-100 text-yellow-800',
                                    ];
                                @endphp

                                @if ($status)
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $colors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($status) }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                                        Not marked
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-1">
                                    <x-filament::button
                                        size="xs"
                                        color="success"
                                        wire:click="setStatus({{ $row['customer_id'] }}, 'visited')"
                                    >
                                        Visited
                                    </x-filament::button>

                                    <x-filament::button
                                        size="xs"
                                        color="danger"
                                        wire:click="setStatus({{ $row['customer_id'] }}, 'missed')"
                                    >
                                        Missed
                                    </x-filament::button>

                                    <x-filament::button
                                        size="xs"
                                        color="warning"
                                        wire:click="setStatus({{ $row['customer_id'] }}, 'cancelled')"
                                    >
                                        Cancelled
                                    </x-filament::button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">
                                No customers with active subscriptions for this schedule on selected date.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament::page>

