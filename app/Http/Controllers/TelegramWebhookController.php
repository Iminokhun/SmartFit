<?php

namespace App\Http\Controllers;

use App\DTO\Telegram\TelegramPreCheckoutData;
use App\DTO\Telegram\TelegramSuccessfulPaymentData;
use App\DTO\Telegram\TelegramUpdateData;
use App\Services\Telegram\TelegramWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramWebhookService $webhookService): JsonResponse
    {
        $secret = (string) config('services.telegram.webhook_secret');

        if ($secret !== '') {
            $incoming = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');
            if (! hash_equals($secret, $incoming)) {
                Log::channel('telegram')->warning('telegram.webhook.invalid_secret', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return response()->json(['ok' => false, 'message' => 'Invalid secret'], 403);
            }
        }

        $preCheckout = TelegramPreCheckoutData::fromArray($request->all());
        if ($preCheckout) {
            Log::channel('telegram')->info('telegram.webhook.pre_checkout.received', [
                'query_id' => $preCheckout->queryId,
                'telegram_user_id' => $preCheckout->telegramUserId,
                'total_amount' => $preCheckout->totalAmount,
                'currency' => $preCheckout->currency,
            ]);

            $webhookService->handlePreCheckoutQuery($preCheckout);

            return response()->json(['ok' => true]);
        }

        $successfulPayment = TelegramSuccessfulPaymentData::fromArray($request->all());
        if ($successfulPayment) {
            Log::channel('telegram')->info('telegram.webhook.successful_payment.received', [
                'telegram_user_id' => $successfulPayment->telegramUserId,
                'telegram_payment_charge_id' => $successfulPayment->telegramPaymentChargeId,
                'total_amount' => $successfulPayment->totalAmount,
                'currency' => $successfulPayment->currency,
            ]);

            $webhookService->handleSuccessfulPayment($successfulPayment);

            return response()->json(['ok' => true]);
        }

        $update = TelegramUpdateData::fromArray($request->all());
        if (! $update) {
            return response()->json(['ok' => true]);
        }

        Log::channel('telegram')->info('telegram.webhook.update.received', [
            'telegram_user_id' => $update->telegramUserId,
            'chat_id' => $update->chatId,
            'text' => $update->text,
        ]);

        $webhookService->handleUpdate($update);

        return response()->json(['ok' => true]);
    }
}
