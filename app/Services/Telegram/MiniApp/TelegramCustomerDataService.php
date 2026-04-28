<?php

namespace App\Services\Telegram\MiniApp;

use App\Models\CustomerCheckin;
use App\Models\CustomerSubscription;
use Carbon\Carbon;

class TelegramCustomerDataService
{
    public function subscriptionSummary(int $customerId): array
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

    public function visitsSummary(int $customerId): array
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

    public function activeSubscriptions(int $customerId): array
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
            $isExpired = $row->status === 'expired' || ($row->end_date && Carbon::parse($row->end_date)->isPast());

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
                'weekdays' => $weekdays,
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
}
