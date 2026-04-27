<?php

namespace App\Services\Telegram;

use App\Jobs\Telegram\SendTelegramMessageJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TelegramBotService
{
    public function sendMessage(int|string $chatId, string $text, array $options = []): void
    {
        SendTelegramMessageJob::dispatch($chatId, $text, $options);
    }

    public function sendStaffMessage(int|string $chatId, string $text, array $options = []): void
    {
        $token = (string) config('services.telegram_staff.bot_token');
        if ($token === '') {
            Log::channel('telegram')->warning('telegram.staff.send_message.missing_token', ['chat_id' => $chatId]);
            return;
        }

        $payload = array_merge(['chat_id' => $chatId, 'text' => $text], $options);

        try {
            $response = Http::timeout(8)
                ->asForm()
                ->post("https://api.telegram.org/bot{$token}/sendMessage", $payload)
                ->json();

            Log::channel('telegram')->info('telegram.staff.send_message', [
                'chat_id' => $chatId,
                'ok' => (bool) ($response['ok'] ?? false),
            ]);
        } catch (\Throwable $e) {
            Log::channel('telegram')->error('telegram.staff.send_message.exception', [
                'chat_id' => $chatId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function sendInvoice(int|string $chatId, array $invoiceData): array
    {
        $token = (string) config('services.telegram.bot_token');
        if ($token === '') {
            return ['ok' => false, 'description' => 'Missing bot token'];
        }

        $payload = array_merge(['chat_id' => $chatId], $invoiceData);

        try {
            $response = Http::timeout(10)
                ->asForm()
                ->post("https://api.telegram.org/bot{$token}/sendInvoice", $payload)
                ->json();

            Log::channel('telegram')->info('telegram.api.send_invoice', [
                'chat_id' => $chatId,
                'ok' => (bool) ($response['ok'] ?? false),
                'description' => (string) ($response['description'] ?? ''),
            ]);

            return is_array($response) ? $response : ['ok' => false, 'description' => 'Invalid Telegram response'];
        } catch (\Throwable $e) {
            Log::channel('telegram')->error('telegram.api.send_invoice.exception', [
                'chat_id' => $chatId,
                'message' => $e->getMessage(),
            ]);

            return ['ok' => false, 'description' => 'sendInvoice exception'];
        }
    }

    public function answerPreCheckoutQuery(string $queryId, bool $ok, ?string $errorMessage = null): array
    {
        $token = (string) config('services.telegram.bot_token');
        if ($token === '') {
            return ['ok' => false, 'description' => 'Missing bot token'];
        }

        $payload = [
            'pre_checkout_query_id' => $queryId,
            'ok' => $ok ? 'true' : 'false',
        ];

        if (! $ok && $errorMessage) {
            $payload['error_message'] = $errorMessage;
        }

        try {
            $response = Http::timeout(10)
                ->asForm()
                ->post("https://api.telegram.org/bot{$token}/answerPreCheckoutQuery", $payload)
                ->json();

            Log::channel('telegram')->info('telegram.api.answer_pre_checkout', [
                'query_id' => $queryId,
                'ok' => (bool) ($response['ok'] ?? false),
                'description' => (string) ($response['description'] ?? ''),
            ]);

            return is_array($response) ? $response : ['ok' => false, 'description' => 'Invalid Telegram response'];
        } catch (\Throwable $e) {
            Log::channel('telegram')->error('telegram.api.answer_pre_checkout.exception', [
                'query_id' => $queryId,
                'message' => $e->getMessage(),
            ]);

            return ['ok' => false, 'description' => 'answerPreCheckoutQuery exception'];
        }
    }
}
