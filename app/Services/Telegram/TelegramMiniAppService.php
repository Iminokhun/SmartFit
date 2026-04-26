<?php

namespace App\Services\Telegram;

use App\Models\Customer;
use App\Models\CustomerCheckin;
use App\Models\CustomerSubscription;
use App\Models\Schedule;
use App\Models\Subscription;
use App\Models\TelegramLink;
use Carbon\Carbon;

class TelegramMiniAppService
{
    public function __construct(private TelegramAuthService $auth) {}

    public function resolveTelegramUserId(string $initData): ?int
    {
        $telegramUser = $this->auth->validateAndExtract($initData, (string) config('services.telegram.bot_token'));
        if (! $telegramUser) {
            return null;
        }

        $telegramUserId = (int) ($telegramUser['id'] ?? 0);

        return $telegramUserId > 0 ? $telegramUserId : null;
    }

    public function catalog(string $initData): array
    {
        $telegramUser = $this->auth->validateAndExtract($initData, (string) config('services.telegram.bot_token'));
        if (! $telegramUser) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Invalid Telegram session.',
            ];
        }

        $telegramUserId = (int) ($telegramUser['id'] ?? 0);
        $link = TelegramLink::query()
            ->where('telegram_user_id', $telegramUserId)
            ->with('customer')
            ->first();

        $plans = Subscription::query()
            ->with('activity:id,name')
            ->withCount([
                'customers as active_customers_count' => fn ($query) => $query->where('status', 'active'),
            ])
            ->orderBy('price')
            ->get()
            ->map(function (Subscription $subscription) {
                return [
                    'id' => (int) $subscription->id,
                    'name' => (string) $subscription->name,
                    'activity' => (string) ($subscription->activity?->name ?? 'General'),
                    'duration_days' => (int) ($subscription->duration_days ?? 0),
                    'visits_limit' => $subscription->visits_limit === null ? null : (int) $subscription->visits_limit,
                    'price' => (float) ($subscription->price ?? 0),
                    'discount' => (float) ($subscription->discount ?? 0),
                    'final_price' => $subscription->finalPrice(),
                    'popular_count' => (int) $subscription->active_customers_count,
                ];
            })
            ->values()
            ->all();

        $activities = collect($plans)
            ->pluck('activity')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'ok' => true,
            'status' => 200,
            'linked' => (bool) ($link && $link->customer),
            'customer' => $link && $link->customer
                ? [
                    'id' => (int) $link->customer->id,
                    'full_name' => (string) $link->customer->full_name,
                ]
                : null,
            'activities' => $activities,
            'plans' => $plans,
        ];
    }

    public function getProfileByInitData(string $initData): array
    {
        $telegramUser = $this->auth->validateAndExtract($initData, (string) config('services.telegram.bot_token'));
        if (! $telegramUser) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Invalid Telegram session.',
            ];
        }

        $telegramUserId = (int) ($telegramUser['id'] ?? 0);

        $link = TelegramLink::query()
            ->where('telegram_user_id', $telegramUserId)
            ->with('customer')
            ->first();

        if (! $link || ! $link->customer) {
            return [
                'ok' => true,
                'status' => 200,
                'linked' => false,
            ];
        }

        return [
            'ok' => true,
            'status' => 200,
            'linked' => true,
            'customer' => [
                'id' => $link->customer->id,
                'full_name' => $link->customer->full_name,
            ],
            'subscription' => $this->subscriptionSummary((int) $link->customer->id),
            'active_subscriptions' => $this->activeSubscriptions((int) $link->customer->id),
            'visits' => $this->visitsSummary((int) $link->customer->id),
            'schedule' => $this->scheduleSummary((int) $link->customer->id),
        ];
    }

    public function linkByIdentity(string $initData, string $phone, string $birthDate): array
    {
        $telegramUser = $this->auth->validateAndExtract($initData, (string) config('services.telegram.bot_token'));
        if (! $telegramUser) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Invalid Telegram session.',
            ];
        }

        $phoneNormalized = $this->normalizePhone($phone);
        if (! $phoneNormalized) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Invalid phone format.',
            ];
        }

        $customer = Customer::query()
            ->whereDate('birth_date', Carbon::parse($birthDate)->toDateString())
            ->whereNotNull('phone')
            ->get()
            ->first(function (Customer $customer) use ($phoneNormalized) {
                return $this->normalizePhone((string) $customer->phone) === $phoneNormalized;
            });

        if (! $customer) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Data mismatch. Please check phone and birth date.',
            ];
        }

        $telegramUserId = (int) ($telegramUser['id'] ?? 0);
        if ($telegramUserId === 0) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Telegram user not found.',
            ];
        }

        $existingByTelegram = TelegramLink::query()
            ->where('telegram_user_id', $telegramUserId)
            ->first();

        if ($existingByTelegram && (int) $existingByTelegram->customer_id !== (int) $customer->id) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'This Telegram account is already linked to another customer.',
            ];
        }

        TelegramLink::updateOrCreate(
            ['customer_id' => (int) $customer->id],
            [
                'telegram_user_id' => $telegramUserId,
                'telegram_username' => $telegramUser['username'] ?? null,
                'first_name' => $telegramUser['first_name'] ?? null,
                'last_name' => $telegramUser['last_name'] ?? null,
                'is_verified' => true,
                'linked_at' => now(),
            ]
        );

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Linked successfully.',
            'customer' => [
                'id' => $customer->id,
                'full_name' => $customer->full_name,
            ],
            'subscription' => $this->subscriptionSummary((int) $customer->id),
            'active_subscriptions' => $this->activeSubscriptions((int) $customer->id),
            'visits' => $this->visitsSummary((int) $customer->id),
            'schedule' => $this->scheduleSummary((int) $customer->id),
        ];
    }

    private function subscriptionSummary(int $customerId): array
    {
        $active = CustomerSubscription::query()
            ->with('subscription')
            ->where('customer_id', $customerId)
            ->where('status', 'active')
            ->orderBy('end_date')
            ->first();

        if (! $active) {
            $frozen = CustomerSubscription::query()
                ->with('subscription')
                ->where('customer_id', $customerId)
                ->where('status', 'frozen')
                ->orderBy('end_date')
                ->first();

            if ($frozen) {
                return [
                    'has_active' => false,
                    'status' => 'frozen',
                    'name' => $frozen->subscription?->name ?? 'Subscription',
                    'end_date' => (string) $frozen->end_date,
                    'days_left' => null,
                    'debt' => (float) ($frozen->debt ?? 0),
                    'payment_status' => (string) ($frozen->payment_status ?? 'unknown'),
                ];
            }

            return [
                'has_active' => false,
                'status' => 'none',
                'name' => null,
                'end_date' => null,
                'days_left' => null,
                'debt' => 0,
                'payment_status' => null,
            ];
        }

        $endDate = $active->end_date ? Carbon::parse($active->end_date) : null;
        $daysLeft = $endDate ? (int) now()->diffInDays($endDate, false) : null;
        $status = ($daysLeft !== null && $daysLeft <= 7) ? 'expiring' : 'active';

        return [
            'has_active' => true,
            'status' => $status,
            'name' => $active->subscription?->name ?? 'Subscription',
            'end_date' => (string) $active->end_date,
            'days_left' => $daysLeft,
            'debt' => (float) ($active->debt ?? 0),
            'payment_status' => (string) ($active->payment_status ?? 'unknown'),
        ];
    }

    private function visitsSummary(int $customerId): array
    {
        $active = CustomerSubscription::query()
            ->where('customer_id', $customerId)
            ->where('status', 'active')
            ->orderBy('end_date')
            ->first();

        if (! $active) {
            return [
                'has_active' => false,
                'left' => null,
                'is_unlimited' => false,
            ];
        }

        if ($active->remaining_visits === null) {
            return [
                'has_active' => true,
                'left' => null,
                'is_unlimited' => true,
            ];
        }

        return [
            'has_active' => true,
            'left' => (int) $active->remaining_visits,
            'is_unlimited' => false,
        ];
    }

    private function activeSubscriptions(int $customerId): array
    {
        $rows = CustomerSubscription::query()
            ->with('subscription:id,name')
            ->where('customer_id', $customerId)
            ->whereIn('status', ['active', 'expired'])
            ->orderByRaw("CASE status WHEN 'active' THEN 1 ELSE 2 END")
            ->orderBy('end_date')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return $rows->map(function (CustomerSubscription $row) {
            $remaining = $row->remaining_visits === null ? 'Unlimited' : (string) $row->remaining_visits;
            $isExpired = $row->status === 'expired' || ($row->end_date && \Carbon\Carbon::parse($row->end_date)->isPast());

            return [
                'name' => $row->subscription?->name ?? 'Subscription',
                'end_date' => (string) $row->end_date,
                'remaining_visits' => $remaining,
                'payment_status' => (string) ($row->payment_status ?? 'unknown'),
                'debt' => (float) ($row->debt ?? 0),
                'is_expired' => $isExpired,
            ];
        })->values()->all();
    }

    public function subscriptionsDetail(int $customerId): array
    {
        $rows = CustomerSubscription::query()
            ->with([
                'subscription:id,name,activity_id,hall_id,trainer_id,allowed_weekdays,time_from,time_to,visits_limit',
                'subscription.activity:id,name',
                'subscription.hall:id,name',
                'subscription.trainer:id,full_name',
            ])
            ->where('customer_id', $customerId)
            ->whereIn('status', ['active', 'pending', 'frozen'])
            ->orderByRaw("CASE status WHEN 'active' THEN 1 WHEN 'pending' THEN 2 WHEN 'frozen' THEN 3 ELSE 4 END")
            ->orderBy('end_date')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $isoMap = [
            'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4,
            'friday' => 5, 'saturday' => 6, 'sunday' => 7,
        ];

        return $rows->map(function (CustomerSubscription $row) use ($isoMap) {
            $sub = $row->subscription;
            $rawDays = is_array($sub?->allowed_weekdays) ? $sub->allowed_weekdays : [];

            // Normalize to ISO day numbers 1(Mon)–7(Sun)
            $weekdays = collect($rawDays)
                ->map(function ($d) use ($isoMap) {
                    if (is_numeric($d)) {
                        return (int) $d;
                    }

                    return $isoMap[strtolower(trim((string) $d))] ?? null;
                })
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();

            return [
                'id' => (int) $row->id,
                'name' => $sub?->name ?? 'Subscription',
                'status' => (string) $row->status,
                'start_date' => (string) $row->start_date,
                'end_date' => (string) $row->end_date,
                'remaining_visits' => $row->remaining_visits === null ? null : (int) $row->remaining_visits,
                'total_visits' => $sub?->visits_limit === null ? null : (int) $sub->visits_limit,
                'is_unlimited' => $row->remaining_visits === null,
                'agreed_price' => (float) ($row->agreed_price ?? 0),
                'paid_amount' => (float) ($row->paid_amount ?? 0),
                'debt' => (float) ($row->debt ?? 0),
                'payment_status' => (string) ($row->payment_status ?? 'unknown'),
                'activity' => $sub?->activity?->name ?? null,
                'hall' => $sub?->hall?->name ?? null,
                'trainer' => $sub?->trainer?->full_name ?? null,
                'weekdays' => $weekdays, // array of ISO ints 1-7
                'time_from' => $sub?->time_from ? Carbon::parse($sub->time_from)->format('H:i') : null,
                'time_to' => $sub?->time_to ? Carbon::parse($sub->time_to)->format('H:i') : null,
            ];
        })->values()->all();
    }

    public function visitsHistory(int $customerId, ?int $subscriptionId = null): array
    {
        $query = CustomerCheckin::query()
            ->where('customer_id', $customerId)
            ->whereNotNull('schedule_id')
            ->with(['schedule:id,hall_id', 'schedule.hall:id,name'])
            ->orderBy('checked_in_at', 'desc')
            ->limit(20);

        if ($subscriptionId) {
            $query->where('customer_subscription_id', $subscriptionId);
        }

        $checkins = $query->get();

        $visits = $checkins->map(fn ($c) => [
            'date' => $c->checked_in_at?->format('d M Y'),
            'time' => $c->checked_in_at?->format('H:i'),
            'hall' => $c->schedule?->hall?->name ?? null,
        ])->values()->all();

        return ['ok' => true, 'visits' => $visits];
    }

    private function scheduleSummary(int $customerId): array
    {
        $activityIds = CustomerSubscription::query()
            ->where('customer_id', $customerId)
            ->where('status', 'active')
            ->whereHas('subscription')
            ->with('subscription:id,activity_id')
            ->get()
            ->pluck('subscription.activity_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($activityIds)) {
            return ['has_items' => false, 'items' => []];
        }

        $schedules = Schedule::query()
            ->with(['activity:id,name', 'hall:id,name', 'staff:id,full_name'])
            ->whereIn('activity_id', $activityIds)
            ->get();

        if ($schedules->isEmpty()) {
            return ['has_items' => false, 'items' => []];
        }

        $todayKey = strtolower(now()->format('l'));
        $todayItems = $schedules
            ->filter(fn (Schedule $schedule) => in_array($todayKey, $this->normalizedDays($schedule), true))
            ->sortBy('start_time')
            ->take(5)
            ->values();

        if ($todayItems->isNotEmpty()) {
            $nowTime = now()->format('H:i:s');
            $nextFound = false;
            $items = $todayItems->map(function (Schedule $schedule) use ($nowTime, &$nextFound) {
                $isPast = ((string) $schedule->end_time) < $nowTime;
                $isNext = ! $isPast && ! $nextFound;
                if ($isNext) {
                    $nextFound = true;
                }

                return $this->formatScheduleItem($schedule, null, true, $isPast, $isNext);
            })->all();

            return ['has_items' => true, 'items' => $items];
        }

        // Fallback: if no classes today, show nearest upcoming classes.
        $upcoming = $schedules
            ->map(function (Schedule $schedule) {
                $next = $this->nextClassDayMeta($schedule);
                if (! $next) {
                    return null;
                }

                return [
                    'schedule' => $schedule,
                    'days_ahead' => $next['days_ahead'],
                    'day_label' => $next['day_label'],
                ];
            })
            ->filter()
            ->sortBy([
                ['days_ahead', 'asc'],
                [fn (array $row) => (string) $row['schedule']->start_time, 'asc'],
            ])
            ->take(5)
            ->values();

        if ($upcoming->isEmpty()) {
            return ['has_items' => false, 'items' => []];
        }

        $items = $upcoming->map(function (array $row) {
            return $this->formatScheduleItem($row['schedule'], $row['day_label'], false);
        })->all();

        return ['has_items' => true, 'items' => $items];
    }

    private function formatScheduleItem(Schedule $schedule, ?string $dayLabel, bool $isToday, bool $isPast = false, bool $isNext = false): array
    {
        return [
            'time_from' => Carbon::parse($schedule->start_time)->format('H:i'),
            'time_to' => Carbon::parse($schedule->end_time)->format('H:i'),
            'activity' => $schedule->activity?->name ?? 'Activity',
            'hall' => $schedule->hall?->name ?? null,
            'trainer' => $schedule->staff?->full_name ?? null,
            'day' => $isToday ? 'Today' : $dayLabel,
            'is_today' => $isToday,
            'is_past' => $isPast,
            'is_next' => $isNext,
        ];
    }

    private function normalizedDays(Schedule $schedule): array
    {
        $days = is_array($schedule->days_of_week) ? $schedule->days_of_week : [];

        return collect($days)
            ->map(fn ($day) => strtolower(trim((string) $day)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function nextClassDayMeta(Schedule $schedule): ?array
    {
        $dayToIndex = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 7,
        ];

        $currentIndex = (int) now()->dayOfWeekIso;
        $bestDelta = null;
        $bestDay = null;

        foreach ($this->normalizedDays($schedule) as $day) {
            $target = $dayToIndex[$day] ?? null;
            if (! $target) {
                continue;
            }

            $delta = ($target - $currentIndex + 7) % 7;
            if ($delta === 0) {
                $delta = 7;
            }

            if ($bestDelta === null || $delta < $bestDelta) {
                $bestDelta = $delta;
                $bestDay = ucfirst($day);
            }
        }

        if ($bestDelta === null || $bestDay === null) {
            return null;
        }

        return [
            'days_ahead' => $bestDelta,
            'day_label' => $bestDay,
        ];
    }

    private function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (! $digits) {
            return null;
        }

        if (str_starts_with($digits, '998') && strlen($digits) === 12) {
            return $digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '998' . substr($digits, 1);
        }

        if (strlen($digits) === 9) {
            return '998' . $digits;
        }

        return $digits;
    }
}
