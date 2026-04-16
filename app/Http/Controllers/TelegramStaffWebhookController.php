<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramStaffWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('services.telegram_staff.webhook_secret');
        if ($secret !== '') {
            $incoming = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');
            if (! hash_equals($secret, $incoming)) {
                Log::channel('telegram')->warning('telegram.staff_webhook.invalid_secret', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return response()->json(['ok' => false, 'message' => 'Invalid secret'], 403);
            }
        }

        $message = (array) $request->input('message', []);
        $chatId = (int) ($message['chat']['id'] ?? 0);
        $text = trim((string) ($message['text'] ?? ''));

        if ($chatId > 0 && ($text === '/start' || str_starts_with($text, '/start '))) {
            $this->sendStartMessage($chatId);
        }

        return response()->json(['ok' => true]);
    }

    private function sendStartMessage(int $chatId): void
    {
        $token = (string) config('services.telegram_staff.bot_token');
        if ($token === '') {
            return;
        }

        $url = route('telegram.staff.scan.show');

        $payload = [
            'chat_id' => $chatId,
            'text' => "Welcome to SmartFit Staff Scanner.\nTap button below to open QR scanner.",
            'reply_markup' => json_encode([
                'keyboard' => [[
                    [
                        'text' => 'Open Scanner',
                        'web_app' => ['url' => $url],
                    ],
                ]],
                'resize_keyboard' => true,
                'one_time_keyboard' => false,
            ], JSON_UNESCAPED_UNICODE),
        ];

        try {
            $response = Http::timeout(8)
                ->asForm()
                ->post("https://api.telegram.org/bot{$token}/sendMessage", $payload)
                ->json();

            Log::channel('telegram')->info('telegram.staff_webhook.send_start_message', [
                'chat_id' => $chatId,
                'ok' => (bool) ($response['ok'] ?? false),
                'description' => (string) ($response['description'] ?? ''),
            ]);
        } catch (\Throwable $e) {
            Log::channel('telegram')->error('telegram.staff_webhook.send_start_message.exception', [
                'chat_id' => $chatId,
                'message' => $e->getMessage(),
            ]);
        }
    }
}

