<?php

namespace App\Services\Telegram\Webhook;

use App\DTO\Telegram\TelegramUpdateData;
use App\Models\TelegramLink;
use App\Services\Telegram\TelegramBotService;

class TelegramWebhookRouter
{
    public function __construct(
        private readonly TelegramBotService $botService,
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

    private function appKeyboard(): string
    {
        return json_encode([
            'keyboard' => [
                [
                    ['text' => 'App', 'web_app' => ['url' => $this->miniAppUrl()]],
                ],
            ],
            'resize_keyboard' => true,
            'is_persistent' => true,
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
