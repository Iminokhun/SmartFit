<?php

namespace App\Services\Telegram;

use App\Models\Customer;
use App\Models\CustomerSubscription;
use App\Models\Subscription;
use App\Models\TelegramLink;
use App\Services\Telegram\MiniApp\TelegramCustomerDataService;
use App\Services\Telegram\MiniApp\TelegramScheduleService;
use Carbon\Carbon;

class TelegramMiniAppService
{
    public function __construct(
        private readonly TelegramAuthService $auth,
        private readonly TelegramCustomerDataService $customerData,
        private readonly TelegramScheduleService $scheduleService,
    ) {}

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

        $customerId = (int) $link->customer->id;

        return [
            'ok' => true,
            'status' => 200,
            'linked' => true,
            'customer' => [
                'id' => $link->customer->id,
                'full_name' => $link->customer->full_name,
            ],
            'subscription' => $this->customerData->subscriptionSummary($customerId),
            'active_subscriptions' => $this->customerData->activeSubscriptions($customerId),
            'visits' => $this->customerData->visitsSummary($customerId),
            'schedule' => $this->scheduleService->scheduleSummary($customerId),
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

        $customerId = (int) $customer->id;

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Linked successfully.',
            'customer' => [
                'id' => $customer->id,
                'full_name' => $customer->full_name,
            ],
            'subscription' => $this->customerData->subscriptionSummary($customerId),
            'active_subscriptions' => $this->customerData->activeSubscriptions($customerId),
            'visits' => $this->customerData->visitsSummary($customerId),
            'schedule' => $this->scheduleService->scheduleSummary($customerId),
        ];
    }

    public function subscriptionsDetail(int $customerId): array
    {
        return $this->customerData->subscriptionsDetail($customerId);
    }

    public function visitsHistory(int $customerId, ?int $subscriptionId = null): array
    {
        return $this->customerData->visitsHistory($customerId, $subscriptionId);
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
