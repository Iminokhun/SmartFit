<?php

namespace App\Services\Telegram;

class TelegramAuthService
{
    /**
     * Validate Telegram WebApp initData and return the user payload.
     *
     * @param  string  $initData  Raw URL-encoded init_data string from Telegram WebApp
     * @param  string  $botToken  Bot token to use for HMAC verification
     * @return array|null  Decoded user array, or null if invalid / expired
     */
    public function validateAndExtract(string $initData, string $botToken): ?array
    {
        parse_str($initData, $parsed);

        $hash = (string) ($parsed['hash'] ?? '');
        if ($hash === '') {
            return null;
        }

        unset($parsed['hash']);
        ksort($parsed);

        $dataCheckString = collect($parsed)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode("\n");

        if ($botToken === '') {
            return null;
        }

        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (! hash_equals($calculatedHash, $hash)) {
            return null;
        }

        $authDate = (int) ($parsed['auth_date'] ?? 0);
        if ($authDate > 0 && now()->timestamp - $authDate > 86400) {
            return null;
        }

        $userJson = (string) ($parsed['user'] ?? '');
        $user = json_decode($userJson, true);

        return is_array($user) ? $user : null;
    }
}
