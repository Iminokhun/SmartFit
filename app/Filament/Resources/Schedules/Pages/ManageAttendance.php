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

    public function setStatus(int $customerId, string $status): void
    {
        $date = Carbon::parse($this->date);
        $schedule = $this->record;

        $visit = Visit::where('schedule_id', $schedule->id)
            ->where('customer_id', $customerId)
            ->whereDate('visited_at', $date)
            ->first();

        if (! $visit) {
            $visit = Visit::create([
                'customer_id' => $customerId,
                'schedule_id' => $schedule->id,
                'visited_at' => $date->setTimeFromTimeString($schedule->start_time),
                'status' => $status,
                'trainer_id' => $schedule->trainer_id,
            ]);
        } else {
            $visit->update(['status' => $status]);
        }

        $this->loadRows();
    }

    protected function loadRows(): void
    {
        $date = Carbon::parse($this->date);
        $schedule = $this->record;

        // 1) Найти всех клиентов с активным абонементом на activity этого расписания в выбранную дату.
        $customerIds = CustomerSubscription::query()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
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

        // 2) Подтянуть существующие визиты на эту дату.
        $visits = Visit::query()
            ->where('schedule_id', $schedule->id)
            ->whereDate('visited_at', $date)
            ->get()
            ->keyBy('customer_id');

        $rows = [];

        foreach ($customerIds as $customerId) {
            $customer = $customers[$customerId] ?? null;

            if (! $customer) {
                continue;
            }

            $visit = $visits->get($customerId);

            $rows[] = [
                'customer_id' => $customerId,
                'customer_name' => $customer->full_name,
                'subscription_name' => optional(
                    $customer->subscriptions
                        ->firstWhere('status', 'active')
                )->subscription->name ?? '',
                'status' => $visit?->status,
            ];
        }

        $this->rows = $rows;
    }
}

