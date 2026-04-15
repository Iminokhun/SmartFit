<?php

namespace App\Http\Controllers;

use App\Services\AiChatService;
use App\Services\Checkin\QrCheckinService;
use App\Services\Telegram\TelegramMiniAppService;
use App\Services\Telegram\TelegramWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramMiniAppController extends Controller
{
    public function show()
    {
        return view('telegram.mini-app');
    }

    public function subscriptions()
    {
        return view('telegram.mini-app-subscriptions');
    }

    public function me(Request $request, TelegramMiniAppService $miniAppService): JsonResponse
    {
        $data = $request->validate([
            'init_data' => ['required', 'string'],
        ]);

        return $this->jsonResponse($miniAppService->getProfileByInitData($data['init_data']));
    }

    public function catalog(Request $request, TelegramMiniAppService $miniAppService): JsonResponse
    {
        $data = $request->validate([
            'init_data' => ['required', 'string'],
        ]);

        return $this->jsonResponse($miniAppService->catalog($data['init_data']));
    }

    public function link(Request $request, TelegramMiniAppService $miniAppService): JsonResponse
    {
        $data = $request->validate([
            'init_data' => ['required', 'string'],
            'phone' => ['required', 'string', 'max:30'],
            'birth_date' => ['required', 'date'],
        ]);

        return $this->jsonResponse($miniAppService->linkByIdentity(
            $data['init_data'],
            $data['phone'],
            $data['birth_date'],
        ));
    }

    public function purchaseInvoice(
        Request $request,
        TelegramMiniAppService $miniAppService,
        TelegramWebhookService $webhookService
    ): JsonResponse {
        $data = $request->validate([
            'init_data' => ['required', 'string'],
            'subscription_id' => ['required', 'integer', 'min:1'],
        ]);

        $telegramUserId = $miniAppService->resolveTelegramUserId($data['init_data']);
        if (! $telegramUserId) {
            Log::channel('telegram')->warning('telegram.purchase.invoice.invalid_session');

            return response()->json([
                'ok' => false,
                'message' => 'Invalid Telegram session.',
            ], 422);
        }

        Log::channel('telegram')->info('telegram.purchase.invoice.request', [
            'telegram_user_id' => $telegramUserId,
            'subscription_id' => (int) $data['subscription_id'],
        ]);

        $result = $webhookService->makeInvoiceForLinkedUser(
            $telegramUserId,
            (int) $data['subscription_id'],
        );

        Log::channel('telegram')->info('telegram.purchase.invoice.result', [
            'telegram_user_id' => $telegramUserId,
            'subscription_id' => (int) $data['subscription_id'],
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => (string) ($result['message'] ?? ''),
        ]);

        return $this->jsonResponse($result);
    }

    public function mySubscriptions(Request $request, TelegramMiniAppService $miniAppService): JsonResponse
    {
        $data = $request->validate([
            'init_data' => ['required', 'string'],
        ]);

        $telegramUserId = $miniAppService->resolveTelegramUserId($data['init_data']);
        if (! $telegramUserId) {
            return response()->json(['ok' => false, 'message' => 'Invalid Telegram session.'], 422);
        }

        $profile = $miniAppService->getProfileByInitData($data['init_data']);
        if (! ($profile['linked'] ?? false)) {
            return response()->json(['ok' => false, 'message' => 'Account not linked.'], 422);
        }

        $customerId = (int) ($profile['customer']['id'] ?? 0);

        return response()->json([
            'ok' => true,
            'subscriptions' => $miniAppService->subscriptionsDetail($customerId),
        ]);
    }

    public function mySubscriptionsPage(): \Illuminate\View\View
    {
        return view('telegram.mini-app-my-subscriptions');
    }

    public function myVisits(Request $request, TelegramMiniAppService $miniAppService): JsonResponse
    {
        $data = $request->validate([
            'init_data'       => ['required', 'string'],
            'subscription_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $telegramUserId = $miniAppService->resolveTelegramUserId($data['init_data']);
        if (! $telegramUserId) {
            return response()->json(['ok' => false, 'message' => 'Invalid Telegram session.'], 422);
        }

        $profile = $miniAppService->getProfileByInitData($data['init_data']);
        if (! ($profile['linked'] ?? false)) {
            return response()->json(['ok' => false, 'message' => 'Account not linked.'], 422);
        }

        $customerId = (int) ($profile['customer']['id'] ?? 0);
        $result = $miniAppService->visitsHistory($customerId, $data['subscription_id'] ?? null);

        return response()->json($result);
    }

    public function chatPage(Request $request, TelegramMiniAppService $miniAppService): \Illuminate\View\View
    {
        return view('telegram.mini-app-chat');
    }

    public function chat(Request $request, TelegramMiniAppService $miniAppService, AiChatService $aiChat): JsonResponse
    {
        $data = $request->validate([
            'init_data' => ['required', 'string'],
            'message'   => ['required', 'string', 'max:500'],
        ]);

        $telegramUserId = $miniAppService->resolveTelegramUserId($data['init_data']);
        if (! $telegramUserId) {
            return response()->json(['ok' => false, 'message' => 'Invalid Telegram session.'], 422);
        }

        $reply = $aiChat->chat($telegramUserId, $data['message']);

        return response()->json(['ok' => true, 'reply' => $reply]);
    }

    public function chatPhoto(Request $request, TelegramMiniAppService $miniAppService, AiChatService $aiChat): JsonResponse
    {
        $data = $request->validate([
            'init_data' => ['required', 'string'],
            'image'     => ['required', 'string'],
            'mime_type' => ['required', 'string'],
        ]);

        $telegramUserId = $miniAppService->resolveTelegramUserId($data['init_data']);
        if (! $telegramUserId) {
            return response()->json(['ok' => false, 'message' => 'Invalid Telegram session.'], 422);
        }

        $reply = $aiChat->chatPhoto($telegramUserId, $data['image'], $data['mime_type']);

        return response()->json(['ok' => true, 'reply' => $reply]);
    }

    public function checkinQr(Request $request, TelegramMiniAppService $miniAppService, QrCheckinService $checkinService): JsonResponse
    {
        $data = $request->validate([
            'init_data' => ['required', 'string'],
        ]);

        $telegramUserId = $miniAppService->resolveTelegramUserId($data['init_data']);
        if (! $telegramUserId) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid Telegram session.',
            ], 422);
        }

        $profile = $miniAppService->getProfileByInitData($data['init_data']);
        if (($profile['ok'] ?? false) !== true || ($profile['linked'] ?? false) !== true) {
            return response()->json([
                'ok' => false,
                'message' => 'Telegram account is not linked.',
            ], 422);
        }

        $customerId = (int) ($profile['customer']['id'] ?? 0);
        if ($customerId <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'Customer not found.',
            ], 422);
        }

        $qrData = $checkinService->issueTokenForCustomer($customerId, 5);

        Log::channel('telegram')->info('telegram.mini_app.checkin_qr.issued', [
            'telegram_user_id' => $telegramUserId,
            'customer_id' => $customerId,
            'expires_at' => $qrData['expires_at'],
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Check-in QR generated.',
            'expires_at' => $qrData['expires_at'],
            'qr_svg' => $qrData['qr_svg'],
            'qr_payload' => $qrData['payload'],
        ]);
    }

    private function jsonResponse(array $result): JsonResponse
    {
        $status = (int) ($result['status'] ?? 200);
        unset($result['status']);

        return response()->json($result, $status);
    }
}
