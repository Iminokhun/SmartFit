<?php

namespace App\Services\Security;

use App\Models\AuthLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuthEventLogger
{
    public static function logSuccess(User $user, string $guard, ?Request $request = null): void
    {
        $request ??= request();

        AuthLog::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'panel' => self::resolvePanel($request),
            'guard' => $guard,
            'status' => 'success',
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    public static function logFail(?string $email, ?string $guard, ?Request $request = null): void
    {
        $request ??= request();

        AuthLog::create([
            'email' => $email,
            'panel' => self::resolvePanel($request),
            'guard' => $guard,
            'status' => 'fail',
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    private static function resolvePanel(?Request $request): ?string
    {
        if (! $request) {
            return null;
        }

        $segment = strtolower((string) $request->segment(1));

        return match ($segment) {
            'admin', 'manager' => $segment,
            default => null,
        };
    }
}

