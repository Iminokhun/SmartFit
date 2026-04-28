<x-filament::page>
    @php
        $initial = strtoupper(substr((string) $this->profileSummary['name'], 0, 1) ?: 'M');
        $photoPath = auth()->user()?->staff?->photo;
        $photoUrl = $photoPath ? \Illuminate\Support\Facades\Storage::url($photoPath) : null;
    @endphp
    <div class="manager-personal space-y-6">
        @if (! auth()->user()?->staff)
            <div class="manager-personal-alert">
                Staff profile not linked. Ask admin to link your user in Staff -> Linked user.
            </div>
        @endif

        <div class="tp-top-row">
            <x-filament::section heading="Manager Card" class="manager-personal-section manager-personal-section--card tp-top-row__card">
                <div class="manager-personal-card">
                    @if ($photoUrl)
                        <img src="{{ $photoUrl }}" alt="Manager photo" class="manager-personal-avatar-image">
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

            <x-filament::section heading="My Shifts" class="manager-personal-section manager-personal-section--shifts tp-top-row__shifts">
                @if (count($this->shiftRows))
                    <div class="tp-shifts-list">
                        @foreach ($this->shiftRows as $shift)
                            <div class="tp-shift-card">
                                <div class="tp-shift-days">
                                    @foreach ($shift['all_days'] as $d)
                                        <span class="tp-shift-day {{ in_array($d, $shift['days']) ? 'tp-shift-day--on' : 'tp-shift-day--off' }}">
                                            {{ strtoupper(substr($d, 0, 2)) }}
                                        </span>
                                    @endforeach
                                </div>
                                <div class="tp-shift-time">
                                    <svg class="tp-shift-clock" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/>
                                        <path d="M10 6v4l2.5 2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                    </svg>
                                    {{ $shift['from'] }} — {{ $shift['to'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="manager-personal-empty">No shifts assigned yet.</div>
                @endif
            </x-filament::section>
        </div>

        <div class="tp-stats-bar">
            @foreach ($this->stats as $stat)
                <div class="tp-stat-card">
                    <div class="tp-stat-value" @if($stat['color']) style="color: {{ $stat['color'] }}" @endif>
                        {{ $stat['value'] }}<span class="tp-stat-suffix">{{ $stat['suffix'] }}</span>
                    </div>
                    <div class="tp-stat-label">{{ $stat['label'] }}</div>
                </div>
            @endforeach
        </div>

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

        </div>
    </div>
</x-filament::page>
