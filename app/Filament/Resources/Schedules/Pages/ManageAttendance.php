<?php

namespace App\Filament\Resources\Schedules\Pages;

use App\Filament\Resources\Schedules\ScheduleResource;
use App\Models\Customer;
use App\Models\CustomerSubscription;
use App\Models\Schedule;
use App\Models\Visit;
use Carbon\Carbon;
use Filament\Resources\Pages\Page;

class ManageAttendance extends Page
{
    protected static string $resource = ScheduleResource::class;

    protected static string $view = 'filament.resources.schedules.pages.manage-attendance';

    public Schedule $record;

    public string $date;

    /**
     * @var array<int, string> Список дат (Y-m-d) для колонок таблицы
     */
    public array $dates = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $rows = [];

    public function mount($record): void
    {
        $this->record = $record instanceof Schedule ? $record : Schedule::findOrFail($record);

        $this->date = now()->toDateString();

        $this->loadRows();
    }

    public function updatedDate(): void
    {
        $this->loadRows();
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

        $this->loadRows();
    }

    protected function loadRows(): void
    {
        $currentDate = Carbon::parse($this->date);
        $schedule = $this->record;

        // Определяем месяц по выбранной дате.
        $monthStart = $currentDate->copy()->startOfMonth();
        $monthEnd = $currentDate->copy()->endOfMonth();

        // Определяем дни недели, в которые проходит это занятие.
        $daysOfWeek = $schedule->days_of_week ?? [];
        $dayNameToCarbon = [
            'monday' => Carbon::MONDAY,
            'tuesday' => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY,
            'thursday' => Carbon::THURSDAY,
            'friday' => Carbon::FRIDAY,
            'saturday' => Carbon::SATURDAY,
            'sunday' => Carbon::SUNDAY,
        ];

        // Формируем список дат в выбранном месяце, когда есть это занятие.
        $dates = [];
        $cursor = $monthStart->copy();
        while ($cursor->lte($monthEnd)) {
            $dayName = strtolower($cursor->englishDayOfWeek);
            if (in_array($dayName, $daysOfWeek, true)) {
                $dates[] = $cursor->toDateString();
            }
            $cursor->addDay();
        }

        $this->dates = $dates;

        // 1) Найти всех клиентов с активным абонементом на activity этого расписания в выбранную дату.
        $customerIds = CustomerSubscription::query()
            ->where('status', 'active')
            // Абонемент пересекается с выбранным месяцем.
            ->whereDate('start_date', '<=', $monthEnd)
            ->whereDate('end_date', '>=', $monthStart)
            ->whereHas('subscription', function ($q) use ($schedule) {
                $q->where('activity_id', $schedule->activity_id);
            })
            ->pluck('customer_id')
            ->unique()
            ->all();

        $customers = Customer::query()
            ->whereIn('id', $customerIds)
            ->with(['subscriptions.subscription'])
            ->get()
            ->keyBy('id');

        // 2) Подтянуть существующие визиты за весь месяц.
        $visits = Visit::query()
            ->where('schedule_id', $schedule->id)
            ->whereBetween('visited_at', [
                $monthStart->copy()->startOfDay(),
                $monthEnd->copy()->endOfDay(),
            ])
            ->get()
            ->groupBy('customer_id');

        $rows = [];

        foreach ($customerIds as $customerId) {
            $customer = $customers[$customerId] ?? null;

            if (! $customer) {
                continue;
            }

            $customerVisits = $visits->get($customerId) ?? collect();

            // Сопоставляем статусы по датам.
            $statusesByDate = [];
            foreach ($dates as $dateString) {
                $visitForDate = $customerVisits
                    ->first(function (Visit $visit) use ($dateString) {
                        return Carbon::parse($visit->visited_at)->toDateString() === $dateString;
                    });

                $statusesByDate[$dateString] = $visitForDate?->status;
            }

            $rows[] = [
                'customer_id' => $customerId,
                'customer_name' => $customer->full_name,
                'subscription_name' => optional(
                    $customer->subscriptions
                        ->firstWhere('status', 'active')
                )->subscription->name ?? '',
                'statuses' => $statusesByDate,
            ];
        }

        $this->rows = $rows;
    }
}

