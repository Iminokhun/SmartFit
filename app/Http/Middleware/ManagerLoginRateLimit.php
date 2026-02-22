<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ManagerLoginRateLimit
{
    private RateLimiter $rateLimiter;

    public function __construct(RateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isManagerLoginAttempt($request)) {
            return $next($request);
        }

        $key = $this->throttleKey($request);

        if ($this->rateLimiter->tooManyAttempts($key, 5)) {
            $seconds = $this->rateLimiter->availableIn($key);

            throw ValidationException::withMessages([
                'email' => "Too many attempts. Try again in {$seconds} seconds.",
            ]);
        }

        $this->rateLimiter->hit($key, 300);

        return $next($request);
    }

    private function isManagerLoginAttempt(Request $request): bool
    {
        if (! $request->isMethod('post')) {
            return false;
        }

        return trim($request->path(), '/') === 'manager/login';
    }

    private function throttleKey(Request $request): string
    {
        $email = Str::lower((string) $request->input('email'));

        return Str::transliterate("{$email}|{$request->ip()}|manager");
    }
}
