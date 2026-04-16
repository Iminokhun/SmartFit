<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\CustomerSubscription;
use App\Models\Schedule;
use App\Models\Visit;
use App\Services\Subscriptions\CustomerSubscriptionLifecycleService;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class TrainerSessionAttendance extends Page
{
    protected static ?string $title = 'Session Attendance';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'sessions/{record}/attendance';

    protected string $view = 'filament.pages.trainer-session-attendance';

    public Schedule $record;
    public string $date;
    public array $dates = [];
    public array $rows = [];

    public function mount($record): void
    {
        $schedule = $record instanceof Schedule ? $record : Schedule::findOrFail($record);
        $staffId = auth()->user()?->staff?->id;

        abort_if(! $staffId || (int) $schedule->trainer_id !== (int) $staffId, 403);

        $this->record = $schedule->load(['activity:id,name', 'staff:id,full_name', 'hall:id,name']);
        $this->date = now()->toDateString();
        $this->loadRows();
    }

    public function updatedDate(): void
    {
        $this->loadRows();
    }

    public function getKpiProperty(): array
    {
        $schedule = $this->record;
        $date = Carbon::parse($this->date);
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $base = Visit::query()
            ->where('schedule_id', $schedule->id)
            ->whereBetween('visited_at', [$start, $end]);

        $visited = (clone $base)->where('status', 'visited')->count();
        $missed = (clone $base)->where('status', 'missed')->count();
        $cancelled = (clone $base)->where('status', 'cancelled')->count();
        $total = $visited + $missed + $cancelled;

        $attendanceRate = $total > 0 ? round(($visited / $total) * 100, 1) : 0.0;
        $noShowRate = $total > 0 ? round(($missed / $total) * 100, 1) : 0.0;

        $capacity = (int) ($schedule->max_participants ?? 0);
        $booked = $total;
        $freePlaces = $capacity > 0 ? max(0, $capacity - $booked) : null;

        return [
            'attendance_rate' => $attendanceRate,
            'booked_capacity' => $capacity > 0 ? "{$booked} / {$capacity}" : 'Unlimited',
            'free_places' => $capacity > 0 ? (string) $freePlaces : 'Unlimited',
            'no_show_rate' => $noShowRate,
        ];
    }

    public function toggleStatus(int $customerId, string $dateString): void
    {
        $schedule = $this->record;
        $date = Carbon::parse($dateString);

        $visit = Visit::where('schedule_id', $schedule->id)
            ->where('customer_id', $customerId)
            ->whereDate('visited_at', $date)
            ->first();

        $currentStatus = $visit?->status;

        $cycle = [
            null => 'visited',
            'visited' => 'missed',
            'missed' => 'cancelled',
            'cancelled' => null,
        ];

        $nextStatus = $cycle[$currentStatus] ?? 'visited';
        $consumingStatuses = ['visited', 'missed'];
        $wasConsuming = in_array($currentStatus, $consumingStatuses, true);
        $isConsuming = in_array($nextStatus, $consumingStatuses, true);

        if ($nextStatus === null && $visit) {
            $visit->delete();
        } elseif ($visit) {
            $visit->update(['status' => $nextStatus]);
        } else {
            Visit::create([
                'customer_id' => $customerId,
                'schedule_id' => $schedule->id,
                'visited_at' => $date->setTimeFromTimeString($schedule->start_time),
                'status' => $nextStatus,
                'trainer_id' => $schedule->trainer_id,
            ]);
        }

        $subscription = $this->resolveActiveSubscription($customerId, $date, $schedule);
        if ($subscription && $subscription->remaining_visits !== null && $wasConsuming !== $isConsuming) {
            $delta = $isConsuming ? 1 : -1;
            app(CustomerSubscriptionLifecycleService::class)
                ->applyVisitDelta($subscription, $delta, $date);
        }

        $this->loadRows();
    }

    protected function resolveActiveSubscription(int $customerId, Carbon $date, Schedule $schedule): ?CustomerSubscription
    {
        return CustomerSubscription::query()
            ->where('customer_id', $customerId)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->whereHas('subscription', fn ($q) => $q->where('activity_id', $schedule->activity_id))
            ->orderByDesc('start_date')
            ->first();
    }

    protected function loadRows(): void
    {
        $currentDate = Carbon::parse($this->date);
        $schedule = $this->record;

        $monthStart = $currentDate->copy()->startOfMonth();
        $monthEnd = $currentDate->copy()->endOfMonth();

        $dates = [];
        $cursor = $monthStart->copy();
        while ($cursor->lte($monthEnd)) {
            $dayName = strtolower($cursor->englishDayOfWeek);
            if (in_array($dayName, $schedule->days_of_week ?? [], true)) {
                $dates[] = $cursor->toDateString();
            }
            $cursor->addDay();
        }

        $this->dates = $dates;

        $customerIds = CustomerSubscription::query()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $monthEnd)
            ->whereDate('end_date', '>=', $monthStart)
            ->whereHas('subscription', fn ($q) => $q->where('activity_id', $schedule->activity_id))
            ->pluck('customer_id')
            ->unique()
            ->all();

        $customers = Customer::query()
            ->whereIn('id', $customerIds)
            ->with(['subscriptions.subscription'])
            ->get()
            ->keyBy('id');

        $sortedCustomerIds = $customers->sortBy('full_name')->keys()->all();

        $visits = Visit::query()
            ->where('schedule_id', $schedule->id)
            ->whereBetween('visited_at', [$monthStart->copy()->startOfDay(), $monthEnd->copy()->endOfDay()])
            ->get()
            ->groupBy('customer_id');

        $rows = [];
        foreach ($sortedCustomerIds as $customerId) {
            $customer = $customers[$customerId] ?? null;
            if (! $customer) {
                continue;
            }

            $customerVisits = $visits->get($customerId) ?? collect();
            $statusesByDate = [];

            foreach ($dates as $dateString) {
                $visitForDate = $customerVisits->first(
                    fn (Visit $visit) => Carbon::parse($visit->visited_at)->toDateString() === $dateString
                );
                $statusesByDate[$dateString] = $visitForDate?->status;
            }

            $rows[] = [
                'customer_id' => $customerId,
                'customer_name' => $customer->full_name,
                'subscription_name' => optional($customer->subscriptions->firstWhere('status', 'active'))->subscription->name ?? '',
                'statuses' => $statusesByDate,
            ];
        }

        $this->rows = $rows;
    }
}
