<?php

namespace App\Services\Checkin;

use App\Models\CheckinToken;
use App\Models\CustomerCheckin;
use App\Models\CustomerSubscription;
use App\Models\Schedule;
use App\Models\Visit;
use App\Services\Subscriptions\CustomerSubscriptionLifecycleService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QrCheckinService
{
    public function __construct(
        private readonly CustomerSubscriptionLifecycleService $subscriptionLifecycle,
    ) {}

    public function issueTokenForCustomer(int $customerId, int $ttlMinutes = 5): array
    {
        $token = Str::random(48);
        $expiresAt = now()->addMinutes(max(1, $ttlMinutes));

        CheckinToken::query()->create([
            'customer_id' => $customerId,
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expiresAt,
        ]);

        $payload = $this->payloadFromToken($token);

        return [
            'token' => $token,
            'payload' => $payload,
            'expires_at' => $expiresAt->toIso8601String(),
            'qr_svg' => $this->makeQrSvg($payload),
        ];
    }

    public function resolveOrConsume(string $rawPayload, ?int $actorUserId = null, ?int $scheduleId = null): array
    {
        $token = $this->extractToken($rawPayload);
        if (! $token) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Invalid QR payload.',
            ];
        }

        $tokenRow = $this->findValidTokenRow($token);
        if (! $tokenRow) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'QR is invalid or expired.',
            ];
        }

        $activeSubscriptions = $this->activeSubscriptionsForCustomer((int) $tokenRow->customer_id, Carbon::today());
        if ($activeSubscriptions->isEmpty()) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'No active subscriptions available for check-in.',
            ];
        }

        if ($activeSubscriptions->count() === 1) {
            $result = $this->consume($rawPayload, (int) $activeSubscriptions->first()->id, $actorUserId, $scheduleId);
            if (($result['ok'] ?? false) === true) {
                $result['auto_consumed'] = true;
            }

            return $result;
        }

        return [
            'ok' => true,
            'status' => 200,
            'requires_selection' => true,
            'customer_id' => (int) $tokenRow->customer_id,
            'options' => $activeSubscriptions
                ->map(fn (CustomerSubscription $row) => $this->subscriptionOption($row))
                ->values()
                ->all(),
        ];
    }

    public function consume(string $rawPayload, int $customerSubscriptionId, ?int $actorUserId = null, ?int $scheduleId = null): array
    {
        $token = $this->extractToken($rawPayload);
        if (! $token) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Invalid QR payload.',
            ];
        }

        return DB::transaction(function () use ($token, $customerSubscriptionId, $actorUserId, $scheduleId): array {
            $tokenRow = CheckinToken::query()
                ->where('token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();

            if (! $tokenRow) {
                return [
                    'ok' => false,
                    'status' => 422,
                    'message' => 'QR is invalid.',
                ];
            }

            if ($tokenRow->used_at !== null) {
                return [
                    'ok' => false,
                    'status' => 422,
                    'message' => 'QR is already used.',
                ];
            }

            if (Carbon::parse($tokenRow->expires_at)->isPast()) {
                return [
                    'ok' => false,
                    'status' => 422,
                    'message' => 'QR is expired.',
                ];
            }

            $subscription = CustomerSubscription::query()
                ->with('subscription:id,name,activity_id,allowed_weekdays,time_from,time_to,max_checkins_per_day')
                ->lockForUpdate()
                ->where('id', $customerSubscriptionId)
                ->where('customer_id', (int) $tokenRow->customer_id)
                ->where('status', 'active')
                ->first();

            if (! $subscription) {
                return [
                    'ok' => false,
                    'status' => 422,
                    'message' => 'Selected subscription is not available.',
                ];
            }

            $today = Carbon::today()->toDateString();
            if ((string) $subscription->start_date > $today || (string) $subscription->end_date < $today) {
                return [
                    'ok' => false,
                    'status' => 422,
                    'message' => 'Selected subscription is out of validity period.',
                ];
            }

            if ($subscription->remaining_visits !== null && (int) $subscription->remaining_visits <= 0) {
                return [
                    'ok' => false,
                    'status' => 422,
                    'message' => 'No visits left for selected subscription.',
                ];
            }

            $now = Carbon::now();
            $isoDay = (int) $now->isoWeekday(); // 1..7

            // 1) Weekday rule
            $allowedWeekdays = $subscription->subscription?->allowed_weekdays;
            if (is_array($allowedWeekdays) && count($allowedWeekdays) > 0) {
                $allowedWeekdays = array_map('intval', $allowedWeekdays);

                if (! in_array($isoDay, $allowedWeekdays, true)) {
                    return [
                        'ok' => false,
                        'status' => 422,
                        'message' => 'This subscription is not allowed on this weekday.',
                    ];
                }
            }

            $timeFrom = $subscription->subscription?->time_from;
            $timeTo = $subscription->subscription?->time_to;

            if ($timeFrom && $timeTo) {
                $currentTime = $now->format('H:i:s');
                $from = Carbon::parse($timeFrom)->format('H:i:s');
                $to = Carbon::parse($timeTo)->format('H:i:s');

                if ($currentTime < $from || $currentTime > $to) {
                    return [
                        'ok' => false,
                        'status' => 422,
                        'message' => 'Check-in is outside allowed time window.',
                    ];
                }
            }

            // 3) Max check-ins per day rule
            $maxPerDay = $subscription->subscription?->max_checkins_per_day;
            if ($maxPerDay !== null) {
                $todayCount = CustomerCheckin::query()
                    ->where('customer_subscription_id', (int) $subscription->id)
                    ->whereDate('checked_in_at', $today)
                    ->count();

                if ($todayCount >= (int) $maxPerDay) {
                    return [
                        'ok' => false,
                        'status' => 422,
                        'message' => 'Daily check-in limit reached for this subscription.',
                    ];
                }
            }

            $scheduleId = $scheduleId ? (int) $scheduleId : null;
            if ($scheduleId) {
                $schedule = Schedule::query()->where('id', $scheduleId)->first();
                if (! $schedule) {
                    return [
                        'ok' => false,
                        'status' => 422,
                        'message' => 'Selected schedule is not available.',
                    ];
                }

                $subscriptionActivityId = (int) ($subscription->subscription?->activity_id ?? 0);
                if ($subscriptionActivityId > 0 && (int) $schedule->activity_id !== $subscriptionActivityId) {
                    return [
                        'ok' => false,
                        'status' => 422,
                        'message' => 'Selected schedule does not match subscription activity.',
                    ];
                }
            }


            $alreadyCheckedInToday = CustomerCheckin::query()
                ->where('customer_subscription_id', (int) $subscription->id)
                ->whereDate('checked_in_at', $today)
                ->exists();

            if ($alreadyCheckedInToday) {
                return [
                    'ok' => false,
                    'status' => 409,
                    'message' => 'This subscription is already checked in today.',
                ];
            }

            // Auto-detect schedule from subscription if not explicitly provided
            $resolvedSchedule = $scheduleId
                ? Schedule::query()->find($scheduleId)
                : Schedule::query()->where('subscription_id', $subscription->subscription_id)->first();

            $resolvedScheduleId = $resolvedSchedule?->id;

            $checkin = CustomerCheckin::query()->create([
                'customer_id' => (int) $subscription->customer_id,
                'customer_subscription_id' => (int) $subscription->id,
                'checkin_token_id' => (int) $tokenRow->id,
                'checked_in_by_user_id' => $actorUserId,
                'schedule_id' => $resolvedScheduleId,
                'checked_in_at' => now(),
            ]);

            // Create a Visit record so ManageAttendance reflects the QR check-in
            if ($resolvedScheduleId) {
                Visit::query()->create([
                    'customer_id' => (int) $subscription->customer_id,
                    'schedule_id' => $resolvedScheduleId,
                    'visited_at'  => now(),
                    'status'      => 'visited',
                    'trainer_id'  => $resolvedSchedule?->trainer_id,
                ]);
            }

            $tokenRow->used_at = now();
            $tokenRow->save();

            if ($subscription->remaining_visits !== null) {
                $this->subscriptionLifecycle->applyVisitDelta($subscription, 1, Carbon::today());
                $subscription->refresh();
            }

            return [
                'ok' => true,
                'status' => 200,
                'requires_selection' => false,
                'message' => 'Check-in registered successfully.',
                'checkin_id' => (int) $checkin->id,
                'customer_id' => (int) $subscription->customer_id,
                'subscription' => $this->subscriptionOption($subscription),
            ];
        });
    }

    private function activeSubscriptionsForCustomer(int $customerId, Carbon $asOf)
    {
        $today = $asOf->toDateString();

        return CustomerSubscription::query()
            ->with('subscription:id,name,activity_id,allowed_weekdays,time_from,time_to,max_checkins_per_day')
            ->where('customer_id', $customerId)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->where(function ($query) {
                $query->whereNull('remaining_visits')
                    ->orWhere('remaining_visits', '>', 0);
            })
            ->orderBy('end_date')
            ->orderBy('created_at')
            ->get();
    }

    private function subscriptionOption(CustomerSubscription $subscription): array
    {
        return [
            'customer_subscription_id' => (int) $subscription->id,
            'subscription_name' => (string) ($subscription->subscription?->name ?? 'Subscription'),
            'activity_id' => (int) ($subscription->subscription?->activity_id ?? 0),
            'start_date' => (string) $subscription->start_date,
            'end_date' => (string) $subscription->end_date,
            'remaining_visits' => $subscription->remaining_visits === null ? null : (int) $subscription->remaining_visits,
            'remaining_visits_label' => $subscription->remaining_visits === null ? 'Unlimited' : (string) $subscription->remaining_visits,
        ];
    }

    private function findValidTokenRow(string $token): ?CheckinToken
    {
        return CheckinToken::query()
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    private function payloadFromToken(string $token): string
    {
        return "SMARTFIT-CHECKIN:{$token}";
    }

    private function extractToken(string $rawPayload): ?string
    {
        $value = trim($rawPayload);
        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'SMARTFIT-CHECKIN:')) {
            return substr($value, strlen('SMARTFIT-CHECKIN:')) ?: null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $query = parse_url($value, PHP_URL_QUERY);
            if (is_string($query)) {
                parse_str($query, $params);
                $token = (string) ($params['token'] ?? $params['t'] ?? '');
                if ($token !== '') {
                    return $token;
                }
            }
        }

        return preg_match('/^[A-Za-z0-9]{24,}$/', $value) === 1 ? $value : null;
    }

    private function makeQrSvg(string $payload): string
    {
        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(240),
                new SvgImageBackEnd()
            )
        );

        return $writer->writeString($payload);
    }
}




