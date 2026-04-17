<?php

namespace App\Jobs\Telegram;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendTelegramMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int|string $chatId,
        public string $text,
        public array $options = [],
    ) {}

    public function handle(): void
    {
        $token = (string) config('services.telegram.bot_token');
        if ($token === '') {
            Log::channel('telegram')->warning('telegram.api.send_message.missing_token', [
                'chat_id' => $this->chatId,
            ]);
            return;
        }

        $payload = array_merge([
            'chat_id' => $this->chatId,
            'text' => $this->text,
        ], $this->options);

        try {
            $response = Http::timeout(8)
                ->asForm()
                ->post("https://api.telegram.org/bot{$token}/sendMessage", $payload)
                ->json();

            Log::channel('telegram')->info('telegram.api.send_message', [
                'chat_id' => $this->chatId,
                'ok' => (bool) ($response['ok'] ?? false),
                'description' => (string) ($response['description'] ?? ''),
            ]);
        } catch (\Throwable $e) {
            Log::channel('telegram')->error('telegram.api.send_message.exception', [
                'chat_id' => $this->chatId,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
