<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ManagerPersonal extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedUserCircle;
    protected static string|null|\UnitEnum $navigationGroup = 'Account';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Profile';
    protected static ?string $title = 'Personal Profile';
    protected static ?string $slug = 'personal';

    protected string $view = 'filament.pages.manager-personal';

    public static function shouldRegisterNavigation(): bool
    {
        return \Filament\Facades\Filament::getCurrentPanel()?->getId() !== 'admin';
    }

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
                ? number_format((float) $staff->salary, 2, '.', ',')
                : '-',
        ];
    }

    public function getStatsProperty(): array
    {
        $staff = auth()->user()?->staff;

        $experience = $staff?->experience_years ?? 0;

        $status = $staff?->status ?? 'active';
        $statusLabel = match ($status) {
            'active'   => 'Active',
            'vacation' => 'Vacation',
            default    => 'Inactive',
        };
        $statusColor = match ($status) {
            'active'   => '#16a34a',
            'vacation' => '#d97706',
            default    => '#dc2626',
        };

        $activeStaff = \App\Models\Staff::where('status', 'active')->count();

        $shiftsCount = $staff ? $staff->shifts()->count() : 0;

        return [
            ['label' => 'Experience', 'value' => $experience,  'suffix' => 'yrs', 'color' => null],
            ['label' => 'Status',     'value' => $statusLabel, 'suffix' => '',    'color' => $statusColor],
            ['label' => 'Shifts',     'value' => $shiftsCount, 'suffix' => '',    'color' => null],
        ];
    }

    public function getShiftRowsProperty(): array
    {
        $staff = auth()->user()?->staff;

        if (! $staff) {
            return [];
        }

        $allDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        return $staff->shifts()
            ->orderBy('start_time')
            ->get()
            ->map(fn ($shift) => [
                'days'     => array_map('strtolower', (array) $shift->days_of_week),
                'all_days' => $allDays,
                'from'     => \Carbon\Carbon::parse($shift->start_time)->format('H:i'),
                'to'       => \Carbon\Carbon::parse($shift->end_time)->format('H:i'),
            ])
            ->all();
    }
}
