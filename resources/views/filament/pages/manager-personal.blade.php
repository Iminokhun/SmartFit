<x-filament::page>
    @php
        $initial = strtoupper(substr((string) $this->profileSummary['name'], 0, 1) ?: 'M');
    @endphp

    <div class="manager-personal space-y-6">
        @if (! auth()->user()?->staff)
            <div class="manager-personal-alert">
                Staff profile not linked. Ask admin to link your user in Staff -> Linked user.
            </div>
        @endif

        <x-filament::section heading="Manager Card" class="manager-personal-section manager-personal-section--card">
            <div class="manager-personal-card">
                <div class="manager-personal-avatar">{{ $initial }}</div>
                <div class="manager-personal-meta">
                    <div class="manager-personal-name">{{ $this->profileSummary['name'] }}</div>
                    <div class="manager-personal-sub">
                        {{ $this->profileSummary['role'] }} - {{ $this->profileSummary['specialization'] }}
                    </div>
                    <div class="manager-personal-badges">
                        <span class="manager-personal-badge">{{ $this->profileSummary['email'] }}</span>
                        <span class="manager-personal-badge">{{ $this->profileSummary['phone'] }}</span>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <div class="grid grid-cols-1 gap-8">
            <x-filament::section heading="Salary" class="manager-personal-section manager-personal-section--salary">
                <div class="manager-personal-salary-grid">
                    <div class="manager-personal-salary-card">
                        <div class="manager-personal-label">Salary type</div>
                        <div class="manager-personal-salary-value">{{ $this->salarySummary['type'] }}</div>
                    </div>
                    <div class="manager-personal-salary-card">
                        <div class="manager-personal-label">Salary amount</div>
                        <div class="manager-personal-salary-value">{{ $this->salarySummary['amount'] }}</div>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section heading="My Shifts" class="manager-personal-section manager-personal-section--shifts">
                @if (count($this->shiftRows))
                    <div class="overflow-x-auto manager-personal-table-wrap">
                        <table class="manager-personal-table">
                            <thead>
                                <tr>
                                    <th>Days</th>
                                    <th>From</th>
                                    <th>To</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->shiftRows as $shift)
                                    <tr>
                                        <td>{{ $shift['days'] }}</td>
                                        <td>{{ $shift['from'] }}</td>
                                        <td>{{ $shift['to'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="manager-personal-empty">
                        No shifts assigned yet.
                    </div>
                @endif
            </x-filament::section>
        </div>
    </div>
</x-filament::page>
