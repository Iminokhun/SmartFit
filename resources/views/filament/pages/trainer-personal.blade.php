<x-filament::page>
    @php
        $initial = strtoupper(substr((string) $this->profileSummary['name'], 0, 1) ?: 'T');
        $photoPath = auth()->user()?->staff?->photo;
        $photoUrl = $photoPath ? \Illuminate\Support\Facades\Storage::url($photoPath) : null;
    @endphp

    <div class="manager-personal space-y-6">
        @if (! auth()->user()?->staff)
            <div class="manager-personal-alert">
                Trainer profile not linked. Ask admin to link your user in Staff -> Linked user.
            </div>
        @endif

        <x-filament::section heading="Trainer Card" class="manager-personal-section manager-personal-section--card">
            <div class="manager-personal-card">
                @if ($photoUrl)
                    <img src="{{ $photoUrl }}" alt="Trainer photo" class="manager-personal-avatar-image">
                @else
                    <div class="manager-personal-avatar">{{ $initial }}</div>
                @endif
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
            <x-filament::section heading="Today Schedule" class="manager-personal-section">
                @if (count($this->todayScheduleRows))
                    <div class="overflow-x-auto manager-personal-table-wrap">
                        <table class="manager-personal-table">
                            <thead>
                                <tr>
                                    <th>Activity</th>
                                    <th>Hall</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->todayScheduleRows as $row)
                                    <tr>
                                        <td>{{ $row['activity'] }}</td>
                                        <td>{{ $row['hall'] }}</td>
                                        <td>{{ $row['time'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="manager-personal-empty">No schedule for today.</div>
                @endif
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
                    <div class="manager-personal-empty">No shifts assigned yet.</div>
                @endif
            </x-filament::section>
        </div>
    </div>
</x-filament::page>

