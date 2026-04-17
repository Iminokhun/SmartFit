<?php

namespace App\DTO\Telegram;

class TelegramPreCheckoutData
{
    public function __construct(
        public readonly string $queryId,
        public readonly int $telegramUserId,
        public readonly int $totalAmount,
        public readonly string $currency,
        public readonly string $payload,
    ) {}

    public static function fromArray(array $payload): ?self
    {
        $query = $payload['pre_checkout_query'] ?? null;
        if (! is_array($query)) {
            return null;
        }

        $queryId = (string) ($query['id'] ?? '');
        $telegramUserId = (int) ($query['from']['id'] ?? 0);
        $totalAmount = (int) ($query['total_amount'] ?? 0);
        $currency = (string) ($query['currency'] ?? '');
        $invoicePayload = (string) ($query['invoice_payload'] ?? '');

        if ($queryId === '' || $telegramUserId <= 0 || $totalAmount <= 0 || $currency === '' || $invoicePayload === '') {
            return null;
        }

        return new self($queryId, $telegramUserId, $totalAmount, $currency, $invoicePayload);
    }
}

