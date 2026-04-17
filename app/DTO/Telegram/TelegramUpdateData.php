<?php

namespace App\DTO\Telegram;

class TelegramUpdateData
{
    public function __construct(
        public readonly ?string $text,
        public readonly int|string|null $chatId,
        public readonly int $telegramUserId,
    ) {}

    public static function fromArray(array $payload): ?self
    {
        $message = $payload['message'] ?? null;
        if (! is_array($message)) {
            return null;
        }

        $text = isset($message['text']) ? trim((string) $message['text']) : null;
        $chatId = $message['chat']['id'] ?? null;
        $telegramUserId = (int) ($message['from']['id'] ?? 0);

        return new self($text, $chatId, $telegramUserId);
    }

    public function isValid(): bool
    {
        return $this->chatId !== null && $this->telegramUserId > 0;
    }

    public function isStartCommand(): bool
    {
        return $this->text === '/start';
    }
}

