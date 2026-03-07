<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class TrainerPersonal extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedUserCircle;
    protected static string|null|\UnitEnum $navigationGroup = 'Account';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Personal';
    protected static ?string $title = 'Trainer Profile';
    protected static ?string $slug = 'personal';

    protected string $view = 'filament.pages.trainer-personal';

    public function getProfileSummaryProperty(): array
    {
        $user = auth()->user();

        return [
            'name' => (string) ($user?->name ?? '-'),
            'email' => (string) ($user?->email ?? '-'),
            'phone' => (string) ($user?->staff?->phone ?? '-'),
            'role' => ucfirst((string) ($user?->role?->name ?? 'trainer')),
            'specialization' => (string) ($user?->staff?->specialization ?? '-'),
        ];
    }

    public function getShiftRowsProperty(): array
    {
        $staff = auth()->user()?->staff;

        if (! $staff) {
            return [];
        }

        return $staff->shifts()
            ->orderBy('start_time')
            ->get()
            ->map(fn ($shift) => [
                'days' => collect((array) $shift->days_of_week)
                    ->map(fn ($day) => ucfirst((string) $day))
                    ->implode(', ') ?: '-',
                'from' => \Carbon\Carbon::parse($shift->start_time)->format('H:i'),
                'to' => \Carbon\Carbon::parse($shift->end_time)->format('H:i'),
            ])
            ->all();
    }

    public function getTodayScheduleRowsProperty(): array
    {
        $staff = auth()->user()?->staff;

        if (! $staff) {
            return [];
        }

        $today = strtolower(now()->format('l'));

        return $staff->schedules()
            ->with(['activity:id,name', 'hall:id,name'])
            ->whereJsonContains('days_of_week', $today)
            ->orderBy('start_time')
            ->get()
            ->map(fn ($schedule) => [
                'activity' => $schedule->activity?->name ?? '-',
                'hall' => $schedule->hall?->name ?? '-',
                'time' => sprintf(
                    '%s - %s',
                    \Carbon\Carbon::parse($schedule->start_time)->format('H:i'),
                    \Carbon\Carbon::parse($schedule->end_time)->format('H:i')
                ),
            ])
            ->all();
    }
}

