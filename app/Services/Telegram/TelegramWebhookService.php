<?php

namespace App\Services\Telegram;

use App\DTO\Telegram\TelegramPreCheckoutData;
use App\DTO\Telegram\TelegramSuccessfulPaymentData;
use App\DTO\Telegram\TelegramUpdateData;
use App\Models\CustomerSubscription;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\TelegramLink;
use App\Services\Subscriptions\CustomerSubscriptionLifecycleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TelegramWebhookService
{
    public function __construct(
        private readonly TelegramBotService $botService,
        private readonly CustomerSubscriptionLifecycleService $subscriptionLifecycle,
    ) {}

    public function handleUpdate(TelegramUpdateData $update): void
    {
        if (! $update->isValid()) {
            return;
        }

        if (! $update->isStartCommand()) {
            return;
        }

        $linked = TelegramLink::query()
            ->where('telegram_user_id', $update->telegramUserId)
            ->with('customer')
            ->first();

        if ($linked && $linked->customer) {
            $this->botService->sendMessage(
                $update->chatId,
                "Welcome back, {$linked->customer->full_name}.\nTap App button to open your profile.",
                ['reply_markup' => $this->appKeyboard()]
            );

            return;
        }

        $this->botService->sendMessage(
            $update->chatId,
            "Welcome.\nTap App button to register and open your profile.",
            ['reply_markup' => $this->appKeyboard()]
        );
    }

    public function handlePreCheckoutQuery(TelegramPreCheckoutData $dto): void
    {
        Log::channel('telegram')->info('telegram.pre_checkout.start', [
            'query_id' => $dto->queryId,
            'telegram_user_id' => $dto->telegramUserId,
            'total_amount' => $dto->totalAmount,
            'currency' => $dto->currency,
        ]);

        $payload = $this->parseInvoicePayload($dto->payload);
        if (! $payload) {
            Log::channel('telegram')->warning('telegram.pre_checkout.invalid_payload', [
                'query_id' => $dto->queryId,
            ]);
            $response = $this->botService->answerPreCheckoutQuery($dto->queryId, false, 'Invalid payload.');
            Log::channel('telegram')->info('telegram.pre_checkout.answer', [
                'query_id' => $dto->queryId,
                'ok' => (bool) ($response['ok'] ?? false),
                'description' => (string) ($response['description'] ?? ''),
            ]);

            return;
        }

        $subscription = Subscription::query()->find($payload['subscription_id']);
        if (! $subscription) {
            Log::channel('telegram')->warning('telegram.pre_checkout.subscription_not_found', [
                'query_id' => $dto->queryId,
                'subscription_id' => $payload['subscription_id'],
            ]);
            $response = $this->botService->answerPreCheckoutQuery($dto->queryId, false, 'Subscription not found.');
            Log::channel('telegram')->info('telegram.pre_checkout.answer', [
                'query_id' => $dto->queryId,
                'ok' => (bool) ($response['ok'] ?? false),
                'description' => (string) ($response['description'] ?? ''),
            ]);

            return;
        }

        if (! $this->isSubscriptionAvailable($subscription)) {
            Log::channel('telegram')->warning('telegram.pre_checkout.subscription_unavailable', [
                'query_id' => $dto->queryId,
                'subscription_id' => (int) $subscription->id,
            ]);
            $response = $this->botService->answerPreCheckoutQuery($dto->queryId, false, 'This subscription is no longer available.');
            Log::channel('telegram')->info('telegram.pre_checkout.answer', [
                'query_id' => $dto->queryId,
                'ok' => (bool) ($response['ok'] ?? false),
                'description' => (string) ($response['description'] ?? ''),
            ]);

            return;
        }

        $expectedAmount = $this->toMinorUnits($subscription->finalPrice());
        if ($dto->totalAmount !== $expectedAmount) {
            Log::channel('telegram')->warning('telegram.pre_checkout.amount_mismatch', [
                'query_id' => $dto->queryId,
                'subscription_id' => (int) $subscription->id,
                'expected_amount' => $expectedAmount,
                'received_amount' => $dto->totalAmount,
            ]);
            $response = $this->botService->answerPreCheckoutQuery($dto->queryId, false, 'Amount mismatch.');
            Log::channel('telegram')->info('telegram.pre_checkout.answer', [
                'query_id' => $dto->queryId,
                'ok' => (bool) ($response['ok'] ?? false),
                'description' => (string) ($response['description'] ?? ''),
            ]);

            return;
        }

        $response = $this->botService->answerPreCheckoutQuery($dto->queryId, true);
        Log::channel('telegram')->info('telegram.pre_checkout.answer', [
            'query_id' => $dto->queryId,
            'ok' => (bool) ($response['ok'] ?? false),
            'description' => (string) ($response['description'] ?? ''),
        ]);
    }

    public function handleSuccessfulPayment(TelegramSuccessfulPaymentData $dto): void
    {
        if (Payment::query()->where('telegram_payment_charge_id', $dto->telegramPaymentChargeId)->exists()) {
            Log::channel('telegram')->info('telegram.payment.duplicate', [
                'telegram_payment_charge_id' => $dto->telegramPaymentChargeId,
            ]);

            return;
        }

        $payload = $this->parseInvoicePayload($dto->invoicePayload);
        if (! $payload) {
            Log::channel('telegram')->warning('telegram.payment.invalid_payload', [
                'telegram_payment_charge_id' => $dto->telegramPaymentChargeId,
            ]);

            return;
        }

        $customerId = (int) $payload['customer_id'];
        $subscriptionId = (int) $payload['subscription_id'];

        $subscription = Subscription::query()->find($subscriptionId);
        if (! $subscription || ! $this->isSubscriptionAvailable($subscription)) {
            Log::channel('telegram')->warning('telegram.payment.subscription_unavailable', [
                'telegram_payment_charge_id' => $dto->telegramPaymentChargeId,
                'subscription_id' => $subscriptionId,
            ]);

            return;
        }

        $amount = $dto->totalAmount / 100;
        $startDate = Carbon::today();
        $initialStatus = $this->subscriptionLifecycle->decideStatusOnPurchase($customerId, $subscription, $startDate);
        $endDate = (clone $startDate)->addDays(max(0, (int) $subscription->duration_days));
        $createdCustomerSubscriptionId = null;

        DB::transaction(function () use ($customerId, $subscription, $startDate, $endDate, $amount, $dto, $initialStatus, &$createdCustomerSubscriptionId): void {
            $customerSubscription = CustomerSubscription::query()->create([
                'customer_id' => $customerId,
                'subscription_id' => (int) $subscription->id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'remaining_visits' => $subscription->visits_limit === null ? null : (int) $subscription->visits_limit,
                'status' => $initialStatus,
            ]);
            $createdCustomerSubscriptionId = (int) $customerSubscription->id;

            Payment::query()->create([
                'customer_id' => $customerId,
                'customer_subscription_id' => (int) $customerSubscription->id,
                'amount' => $amount,
                'method' => 'online',
                'status' => 'paid',
                'description' => 'Telegram payment',
                'telegram_payment_charge_id' => $dto->telegramPaymentChargeId,
                'provider_payment_charge_id' => $dto->providerPaymentChargeId,
            ]);

            $customerSubscription->recalculatePaymentSummary();

            Log::channel('telegram')->info('telegram.payment.persisted', [
                'telegram_payment_charge_id' => $dto->telegramPaymentChargeId,
                'customer_id' => $customerId,
                'customer_subscription_id' => (int) $customerSubscription->id,
                'subscription_id' => (int) $subscription->id,
                'amount' => $amount,
                'status' => $initialStatus,
            ]);
        });

        $this->subscriptionLifecycle->syncActivityQueue(
            $customerId,
            (int) $subscription->activity_id,
            $startDate,
        );

        $effectiveStatus = $createdCustomerSubscriptionId
            ? (string) (CustomerSubscription::query()->whereKey($createdCustomerSubscriptionId)->value('status') ?? $initialStatus)
            : $initialStatus;

        $this->botService->sendMessage(
            $dto->chatId,
            $this->buildPaymentSuccessMessage(
                planName: (string) $subscription->name,
                amount: $amount,
                status: $effectiveStatus,
            ),
            [
                'parse_mode' => 'HTML',
                'reply_markup' => $this->miniAppInlineKeyboard($effectiveStatus),
            ]
        );
        Log::channel('telegram')->info('telegram.payment.success_notification_queued', [
            'chat_id' => $dto->chatId,
            'telegram_payment_charge_id' => $dto->telegramPaymentChargeId,
        ]);
    }

    public function makeInvoiceForLinkedUser(int $telegramUserId, int $subscriptionId): array
    {
        Log::channel('telegram')->info('telegram.invoice.prepare.start', [
            'telegram_user_id' => $telegramUserId,
            'subscription_id' => $subscriptionId,
        ]);

        $link = TelegramLink::query()
            ->where('telegram_user_id', $telegramUserId)
            ->with('customer')
            ->first();

        if (! $link || ! $link->customer) {
            Log::channel('telegram')->warning('telegram.invoice.prepare.not_linked', [
                'telegram_user_id' => $telegramUserId,
            ]);

            return ['ok' => false, 'status' => 422, 'message' => 'Telegram account is not linked.'];
        }

        $subscription = Subscription::query()->with('activity:id,name')->find($subscriptionId);
        if (! $subscription) {
            Log::channel('telegram')->warning('telegram.invoice.prepare.subscription_not_found', [
                'telegram_user_id' => $telegramUserId,
                'subscription_id' => $subscriptionId,
            ]);

            return ['ok' => false, 'status' => 404, 'message' => 'Subscription not found.'];
        }

        if (! $this->isSubscriptionAvailable($subscription)) {
            Log::channel('telegram')->warning('telegram.invoice.prepare.subscription_unavailable', [
                'telegram_user_id' => $telegramUserId,
                'subscription_id' => $subscriptionId,
            ]);

            return ['ok' => false, 'status' => 422, 'message' => 'Subscription limit is already full.'];
        }

        $today = Carbon::today()->toDateString();

        $activeSamePlan = CustomerSubscription::query()
            ->where('customer_id', (int) $link->customer->id)
            ->where('subscription_id', (int) $subscription->id)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderByDesc('id')
            ->first();

        if ($activeSamePlan && $activeSamePlan->remaining_visits === null) {
            Log::channel('telegram')->warning('telegram.invoice.prepare.duplicate_active_subscription', [
                'telegram_user_id' => $telegramUserId,
                'customer_id' => (int) $link->customer->id,
                'subscription_id' => (int) $subscription->id,
                'remaining_visits' => null,
            ]);

            return ['ok' => false, 'status' => 422, 'message' => 'You can buy this unlimited plan after current one expires.'];
        }

        if ($activeSamePlan && (int) $activeSamePlan->remaining_visits > 1) {
            Log::channel('telegram')->warning('telegram.invoice.prepare.repurchase_too_early', [
                'telegram_user_id' => $telegramUserId,
                'customer_id' => (int) $link->customer->id,
                'subscription_id' => (int) $subscription->id,
                'remaining_visits' => (int) $activeSamePlan->remaining_visits,
            ]);

            return [
                'ok' => false,
                'status' => 422,
                'message' => 'You can buy this plan when only 1 visit is left.',
            ];
        }

        $providerToken = (string) config('services.telegram.provider_token');
        if ($providerToken === '') {
            Log::channel('telegram')->warning('telegram.invoice.prepare.missing_provider_token', [
                'telegram_user_id' => $telegramUserId,
            ]);

            return ['ok' => false, 'status' => 422, 'message' => 'Missing TELEGRAM_PROVIDER_TOKEN (or PAYME_TOKEN).'];
        }

        $title = (string) $subscription->name;
        $description = ($subscription->activity?->name ?? 'Activity') . ', ' . (int) $subscription->duration_days . ' days';
        $finalPrice = $subscription->finalPrice();
        $nonce = substr(base_convert((string) now()->timestamp, 10, 36), -8);
        $payload = sprintf(
            'cs_%d_%d_%s',
            (int) $link->customer->id,
            (int) $subscription->id,
            $nonce,
        );

        $response = $this->botService->sendInvoice((int) $telegramUserId, [
            'title' => $title,
            'description' => $description,
            'payload' => $payload,
            'provider_token' => $providerToken,
            'currency' => 'UZS',
            'prices' => json_encode([
                ['label' => $title, 'amount' => $this->toMinorUnits($finalPrice)],
            ], JSON_UNESCAPED_UNICODE),
            'start_parameter' => 'subscription_' . $subscription->id,
        ]);

        Log::channel('telegram')->info('telegram.invoice.prepare.payload', [
            'telegram_user_id' => $telegramUserId,
            'subscription_id' => $subscriptionId,
            'payload' => $payload,
            'payload_len' => strlen($payload),
        ]);

        Log::channel('telegram')->info('telegram.invoice.prepare.telegram_response', [
            'telegram_user_id' => $telegramUserId,
            'subscription_id' => $subscriptionId,
            'ok' => (bool) ($response['ok'] ?? false),
            'description' => (string) ($response['description'] ?? ''),
        ]);

        if (($response['ok'] ?? false) !== true) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => (string) ($response['description'] ?? 'Failed to send invoice.'),
            ];
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Invoice sent to Telegram chat.',
        ];
    }

    private function appKeyboard(): string
    {
        $miniAppUrl = $this->miniAppUrl();

        return json_encode([
            'keyboard' => [
                [
                    ['text' => 'App', 'web_app' => ['url' => $miniAppUrl]],
                ],
            ],
            'resize_keyboard' => true,
            'is_persistent' => true,
        ], JSON_UNESCAPED_UNICODE);
    }

    private function parseInvoicePayload(string $payload): ?array
    {
        // New compact format: cs_{customerId}_{subscriptionId}_{nonce}
        if (preg_match('/^cs_(\d+)_(\d+)_([a-z0-9]+)$/i', $payload, $m) === 1) {
            return [
                'customer_id' => (int) $m[1],
                'subscription_id' => (int) $m[2],
            ];
        }

        // Backward compatibility for old JSON payload.
        $decoded = json_decode($payload, true);
        if (is_array($decoded)) {
            $customerId = (int) ($decoded['customer_id'] ?? 0);
            $subscriptionId = (int) ($decoded['subscription_id'] ?? 0);
            if ($customerId > 0 && $subscriptionId > 0) {
                return [
                    'customer_id' => $customerId,
                    'subscription_id' => $subscriptionId,
                ];
            }
        }

        return null;
    }

    private function isSubscriptionAvailable(Subscription $subscription): bool
    {
        if ($subscription->visits_limit === null) {
            return true;
        }

        $activeCount = CustomerSubscription::query()
            ->where('subscription_id', (int) $subscription->id)
            ->where('status', 'active')
            ->count();

        return $activeCount < (int) $subscription->visits_limit;
    }

    private function toMinorUnits(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private function buildPaymentSuccessMessage(string $planName, float $amount, string $status): string
    {
        $safePlan = htmlspecialchars($planName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $formattedAmount = number_format($amount, 0, '.', ' ');
        $isPending = $status === 'pending';
        $statusText = $isPending ? 'Queued ⏳' : 'Active ✅';
        $summaryText = $isPending
            ? 'Your purchase is completed. This plan is queued and will activate automatically.'
            : 'Your purchase is completed. Your plan is active now.';

        return "<b>Successful transaction</b> 💎\n\n"
            . "Thank you for choosing <b>SmartFit</b>! {$summaryText}\n\n"
            . "— <b>Service:</b> {$safePlan}\n"
            . "— <b>Amount:</b> <code>{$formattedAmount} UZS</code>\n"
            . "— <b>Status:</b> {$statusText}\n\n"
            . "<i>Your QR code is available in Mini App.</i>";
    }

    private function miniAppInlineKeyboard(string $status): string
    {
        $miniAppUrl = $this->miniAppUrl();
        $buttonText = $status === 'pending' ? 'Open Mini App (Queue)' : 'Open Mini App';

        return json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $buttonText, 'web_app' => ['url' => $miniAppUrl]],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    private function miniAppUrl(): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $url = $baseUrl . '/telegram/mini-app';

        if (str_starts_with($url, 'http://')) {
            return 'https://' . substr($url, 7);
        }

        return $url;
    }
}
