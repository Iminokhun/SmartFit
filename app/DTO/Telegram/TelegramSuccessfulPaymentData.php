<?php

namespace App\DTO\Telegram;

class TelegramSuccessfulPaymentData
{
    public function __construct(
        public readonly int $telegramUserId,
        public readonly int|string|null $chatId,
        public readonly int $totalAmount,
        public readonly string $currency,
        public readonly string $invoicePayload,
        public readonly string $telegramPaymentChargeId,
        public readonly string $providerPaymentChargeId,
    ) {}

    public static function fromArray(array $payload): ?self
    {
        $message = $payload['message'] ?? null;
        if (! is_array($message)) {
            return null;
        }

        $payment = $message['successful_payment'] ?? null;
        if (! is_array($payment)) {
            return null;
        }

        $telegramUserId = (int) ($message['from']['id'] ?? 0);
        $chatId = $message['chat']['id'] ?? null;
        $totalAmount = (int) ($payment['total_amount'] ?? 0);
        $currency = (string) ($payment['currency'] ?? '');
        $invoicePayload = (string) ($payment['invoice_payload'] ?? '');
        $telegramPaymentChargeId = (string) ($payment['telegram_payment_charge_id'] ?? '');
        $providerPaymentChargeId = (string) ($payment['provider_payment_charge_id'] ?? '');

        if ($telegramUserId <= 0 || $chatId === null || $totalAmount <= 0 || $currency === '' || $invoicePayload === '' || $telegramPaymentChargeId === '') {
            return null;
        }

        return new self(
            $telegramUserId,
            $chatId,
            $totalAmount,
            $currency,
            $invoicePayload,
            $telegramPaymentChargeId,
            $providerPaymentChargeId,
        );
    }
}

