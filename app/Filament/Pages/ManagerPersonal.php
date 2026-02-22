<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ManagerPersonal extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedUserCircle;
    protected static string|null|\UnitEnum $navigationGroup = 'Account';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Personal';
    protected static ?string $title = 'Personal Profile';
    protected static ?string $slug = 'personal';

    protected string $view = 'filament.pages.manager-personal';

    public function getProfileSummaryProperty(): array
    {
        $user = auth()->user();

        return [
            'name' => (string) ($user?->name ?? '-'),
            'email' => (string) ($user?->email ?? '-'),
            'phone' => (string) ($user?->staff?->phone ?? '-'),
            'role' => ucfirst((string) ($user?->role?->name ?? 'manager')),
            'specialization' => (string) ($user?->staff?->specialization ?? '-'),
        ];
    }

    public function getSalarySummaryProperty(): array
    {
        $staff = auth()->user()?->staff;

        return [
            'type' => $staff?->salary_type
                ? ucfirst(str_replace('_', ' ', (string) $staff->salary_type))
                : '-',
            'amount' => $staff?->salary !== null
                ? '$' . number_format((float) $staff->salary, 2, '.', ',')
                : '-',
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
}
