<?php

namespace App\Services\Subscriptions;

use App\Models\CustomerSubscription;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CustomerSubscriptionLifecycleService
{
    public function decideStatusOnPurchase(int $customerId, Subscription $subscription, ?Carbon $asOf = null): string
    {
        $asOf ??= Carbon::today();
        $activityId = (int) $subscription->activity_id;

        $this->syncActivityQueue($customerId, $activityId, $asOf);

        $hasBlockingActive = CustomerSubscription::query()
            ->where('customer_id', $customerId)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $asOf->toDateString())
            ->whereDate('end_date', '>=', $asOf->toDateString())
            ->whereHas('subscription', fn ($query) => $query->where('activity_id', $activityId))
            ->where(function ($query) {
                $query->whereNull('remaining_visits')
                    ->orWhere('remaining_visits', '>', 0);
            })
            ->exists();

        return $hasBlockingActive ? 'pending' : 'active';
    }

    public function applyVisitDelta(CustomerSubscription $subscription, int $delta, ?Carbon $asOf = null): void
    {
        if ($delta === 0) {
            return;
        }

        $asOf ??= Carbon::today();

        DB::transaction(function () use ($subscription, $delta, $asOf): void {
            $locked = CustomerSubscription::query()
                ->with('subscription:id,activity_id,visits_limit')
                ->lockForUpdate()
                ->find($subscription->id);

            if (! $locked || $locked->remaining_visits === null) {
                return;
            }

            $current = (int) $locked->remaining_visits;
            $next = max(0, $current - $delta);

            $limit = $locked->subscription?->visits_limit;
            if ($limit !== null) {
                $next = min((int) $limit, $next);
            }

            if ($next !== $current) {
                $locked->remaining_visits = $next;
                $locked->save();
            }

            $activityId = (int) ($locked->subscription?->activity_id ?? 0);
            if ($activityId > 0) {
                $this->syncActivityQueue((int) $locked->customer_id, $activityId, $asOf);
            }
        });
    }

    public function syncActivityQueue(int $customerId, int $activityId, ?Carbon $asOf = null): void
    {
        if ($activityId <= 0) {
            return;
        }

        $asOf ??= Carbon::today();
        $today = $asOf->toDateString();

        DB::transaction(function () use ($customerId, $activityId, $asOf, $today): void {
            $subscriptions = CustomerSubscription::query()
                ->where('customer_id', $customerId)
                ->whereIn('status', ['active', 'pending'])
                ->whereHas('subscription', fn ($query) => $query->where('activity_id', $activityId))
                ->with('subscription:id,activity_id,duration_days,visits_limit')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            foreach ($subscriptions as $customerSubscription) {
                if ($customerSubscription->status !== 'active') {
                    continue;
                }

                $noVisitsLeft = $customerSubscription->remaining_visits !== null
                    && (int) $customerSubscription->remaining_visits <= 0;

                if ($this->isPastEndDate($customerSubscription, $today) || $noVisitsLeft) {
                    $customerSubscription->status = 'expired';
                    $customerSubscription->save();
                }
            }

            $hasActive = $subscriptions->contains(function (CustomerSubscription $customerSubscription) use ($today): bool {
                if ($customerSubscription->status !== 'active') {
                    return false;
                }

                if ($this->isPastEndDate($customerSubscription, $today)) {
                    return false;
                }

                if ($customerSubscription->remaining_visits !== null && (int) $customerSubscription->remaining_visits <= 0) {
                    return false;
                }

                return true;
            });

            if ($hasActive) {
                return;
            }

            $nextPending = $subscriptions->first(fn (CustomerSubscription $customerSubscription) => $customerSubscription->status === 'pending');
            if (! $nextPending || ! $nextPending->subscription) {
                return;
            }

            $startDate = $asOf->copy();
            $endDate = (clone $startDate)->addDays(max(0, (int) $nextPending->subscription->duration_days));

            $nextPending->status = 'active';
            $nextPending->start_date = $startDate->toDateString();
            $nextPending->end_date = $endDate->toDateString();
            $nextPending->remaining_visits = $nextPending->subscription->visits_limit === null
                ? null
                : (int) $nextPending->subscription->visits_limit;
            $nextPending->save();
        });
    }

    private function isPastEndDate(CustomerSubscription $customerSubscription, string $today): bool
    {
        return (string) $customerSubscription->end_date < $today;
    }
}

