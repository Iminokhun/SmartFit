{{--<x-filament::page>--}}
{{--    @php--}}
{{--        $cards = [--}}
{{--            ['label' => 'Revenue', 'value' => $metrics['revenue'], 'type' => 'money'],--}}
{{--            ['label' => 'Expenses', 'value' => $metrics['expenses'], 'type' => 'money'],--}}
{{--            ['label' => 'Profit', 'value' => $metrics['profit'], 'type' => 'money'],--}}
{{--            ['label' => 'Debt', 'value' => $metrics['debt'], 'type' => 'money'],--}}
{{--            ['label' => 'New customers', 'value' => $metrics['newCustomers'], 'type' => 'count'],--}}
{{--            ['label' => 'Active subscriptions', 'value' => $metrics['activeSubscriptions'], 'type' => 'count'],--}}
{{--            ['label' => 'ARPU', 'value' => $metrics['arpu'], 'type' => 'money'],--}}
{{--        ];--}}

{{--        $formatMoney = fn ($value) => number_format((float) $value, 2);--}}
{{--    @endphp--}}

{{--    <div class="space-y-6">--}}
{{--        <x-filament::section heading="Filters">--}}
{{--            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">--}}
{{--                <div>--}}
{{--                    <div class="text-sm font-medium text-gray-700">Period</div>--}}
{{--                    <x-filament::input.wrapper>--}}
{{--                        <x-filament::input.select wire:model.live="period">--}}
{{--                            @foreach ($periodOptions as $value => $label)--}}
{{--                                <option value="{{ $value }}">{{ $label }}</option>--}}
{{--                            @endforeach--}}
{{--                        </x-filament::input.select>--}}
{{--                    </x-filament::input.wrapper>--}}
{{--                </div>--}}

{{--                <div>--}}
{{--                    <div class="text-sm font-medium text-gray-700">From</div>--}}
{{--                    <x-filament::input.wrapper>--}}
{{--                        <x-filament::input--}}
{{--                            type="date"--}}
{{--                            wire:model.live="from"--}}
{{--                            :disabled="$period !== 'range'"--}}
{{--                        />--}}
{{--                    </x-filament::input.wrapper>--}}
{{--                </div>--}}

{{--                <div>--}}
{{--                    <div class="text-sm font-medium text-gray-700">Until</div>--}}
{{--                    <x-filament::input.wrapper>--}}
{{--                        <x-filament::input--}}
{{--                            type="date"--}}
{{--                            wire:model.live="until"--}}
{{--                            :disabled="$period !== 'range'"--}}
{{--                        />--}}
{{--                    </x-filament::input.wrapper>--}}
{{--                </div>--}}

{{--                <div>--}}
{{--                    <div class="text-sm font-medium text-gray-700">Activity</div>--}}
{{--                    <x-filament::input.wrapper>--}}
{{--                        <x-filament::input.select wire:model.live="activityId">--}}
{{--                            <option value="">All</option>--}}
{{--                            @foreach ($activities as $id => $name)--}}
{{--                                <option value="{{ $id }}">{{ $name }}</option>--}}
{{--                            @endforeach--}}
{{--                        </x-filament::input.select>--}}
{{--                    </x-filament::input.wrapper>--}}
{{--                </div>--}}

{{--                <div>--}}
{{--                    <div class="text-sm font-medium text-gray-700">Payment method</div>--}}
{{--                    <x-filament::input.wrapper>--}}
{{--                        <x-filament::input.select wire:model.live="paymentMethod">--}}
{{--                            <option value="">All</option>--}}
{{--                            @foreach ($paymentMethods as $value => $label)--}}
{{--                                <option value="{{ $value }}">{{ $label }}</option>--}}
{{--                            @endforeach--}}
{{--                        </x-filament::input.select>--}}
{{--                    </x-filament::input.wrapper>--}}
{{--                </div>--}}

{{--                <div>--}}
{{--                    <div class="text-sm font-medium text-gray-700">Payment status</div>--}}
{{--                    <x-filament::input.wrapper>--}}
{{--                        <x-filament::input.select wire:model.live="paymentStatus">--}}
{{--                            <option value="">Paid + Partial</option>--}}
{{--                            @foreach ($paymentStatuses as $value => $label)--}}
{{--                                <option value="{{ $value }}">{{ $label }}</option>--}}
{{--                            @endforeach--}}
{{--                        </x-filament::input.select>--}}
{{--                    </x-filament::input.wrapper>--}}
{{--                </div>--}}
{{--            </div>--}}

{{--            <div class="mt-3 text-xs text-gray-500">--}}
{{--                Revenue is calculated from paid and partial payments only. Range: {{ $rangeLabel }}--}}
{{--            </div>--}}
{{--        </x-filament::section>--}}

{{--        <x-filament::section heading="KPI">--}}
{{--            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">--}}
{{--                @foreach ($cards as $card)--}}
{{--                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">--}}
{{--                        <div class="text-xs uppercase tracking-wide text-gray-500">--}}
{{--                            {{ $card['label'] }}--}}
{{--                        </div>--}}
{{--                        <div class="mt-2 text-2xl font-semibold text-gray-900">--}}
{{--                            @if ($card['type'] === 'money')--}}
{{--                                {{ $formatMoney($card['value']) }} UZS--}}
{{--                            @else--}}
{{--                                {{ number_format((int) $card['value']) }}--}}
{{--                            @endif--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                @endforeach--}}
{{--            </div>--}}
{{--        </x-filament::section>--}}

{{--        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">--}}
{{--            <x-filament::section heading="Top Subscriptions">--}}
{{--                <div class="overflow-x-auto">--}}
{{--                    <table class="min-w-full divide-y divide-gray-200 text-sm">--}}
{{--                        <thead class="bg-gray-50">--}}
{{--                            <tr>--}}
{{--                                <th class="px-3 py-2 text-left font-medium text-gray-600">Subscription</th>--}}
{{--                                <th class="px-3 py-2 text-right font-medium text-gray-600">Revenue</th>--}}
{{--                            </tr>--}}
{{--                        </thead>--}}
{{--                        <tbody class="divide-y divide-gray-100 bg-white">--}}
{{--                            @forelse ($topSubscriptions as $row)--}}
{{--                                <tr>--}}
{{--                                    <td class="px-3 py-2 text-gray-900">{{ $row->name }}</td>--}}
{{--                                    <td class="px-3 py-2 text-right text-gray-900">--}}
{{--                                        {{ $formatMoney($row->total) }} UZS--}}
{{--                                    </td>--}}
{{--                                </tr>--}}
{{--                            @empty--}}
{{--                                <tr>--}}
{{--                                    <td colspan="2" class="px-3 py-6 text-center text-gray-500">No data</td>--}}
{{--                                </tr>--}}
{{--                            @endforelse--}}
{{--                        </tbody>--}}
{{--                    </table>--}}
{{--                </div>--}}
{{--            </x-filament::section>--}}

{{--            <x-filament::section heading="Top Activities">--}}
{{--                <div class="overflow-x-auto">--}}
{{--                    <table class="min-w-full divide-y divide-gray-200 text-sm">--}}
{{--                        <thead class="bg-gray-50">--}}
{{--                            <tr>--}}
{{--                                <th class="px-3 py-2 text-left font-medium text-gray-600">Activity</th>--}}
{{--                                <th class="px-3 py-2 text-right font-medium text-gray-600">Revenue</th>--}}
{{--                            </tr>--}}
{{--                        </thead>--}}
{{--                        <tbody class="divide-y divide-gray-100 bg-white">--}}
{{--                            @forelse ($topActivities as $row)--}}
{{--                                <tr>--}}
{{--                                    <td class="px-3 py-2 text-gray-900">{{ $row->name }}</td>--}}
{{--                                    <td class="px-3 py-2 text-right text-gray-900">--}}
{{--                                        {{ $formatMoney($row->total) }} UZS--}}
{{--                                    </td>--}}
{{--                                </tr>--}}
{{--                            @empty--}}
{{--                                <tr>--}}
{{--                                    <td colspan="2" class="px-3 py-6 text-center text-gray-500">No data</td>--}}
{{--                                </tr>--}}
{{--                            @endforelse--}}
{{--                        </tbody>--}}
{{--                    </table>--}}
{{--                </div>--}}
{{--            </x-filament::section>--}}

{{--            <x-filament::section heading="Top Customers">--}}
{{--                <div class="overflow-x-auto">--}}
{{--                    <table class="min-w-full divide-y divide-gray-200 text-sm">--}}
{{--                        <thead class="bg-gray-50">--}}
{{--                            <tr>--}}
{{--                                <th class="px-3 py-2 text-left font-medium text-gray-600">Customer</th>--}}
{{--                                <th class="px-3 py-2 text-right font-medium text-gray-600">Revenue</th>--}}
{{--                            </tr>--}}
{{--                        </thead>--}}
{{--                        <tbody class="divide-y divide-gray-100 bg-white">--}}
{{--                            @forelse ($topCustomers as $row)--}}
{{--                                <tr>--}}
{{--                                    <td class="px-3 py-2 text-gray-900">{{ $row->name }}</td>--}}
{{--                                    <td class="px-3 py-2 text-right text-gray-900">--}}
{{--                                        {{ $formatMoney($row->total) }} UZS--}}
{{--                                    </td>--}}
{{--                                </tr>--}}
{{--                            @empty--}}
{{--                                <tr>--}}
{{--                                    <td colspan="2" class="px-3 py-6 text-center text-gray-500">No data</td>--}}
{{--                                </tr>--}}
{{--                            @endforelse--}}
{{--                        </tbody>--}}
{{--                    </table>--}}
{{--                </div>--}}
{{--            </x-filament::section>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--</x-filament::page>--}}
