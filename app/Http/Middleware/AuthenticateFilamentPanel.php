<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateFilamentPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Filament::auth();

        if (! $guard->check()) {
            return redirect()->guest(Filament::getLoginUrl());
        }

        auth()->shouldUse(Filament::getAuthGuard());

        $user = $guard->user();
        $panel = Filament::getCurrentOrDefaultPanel();

        $canAccess = $user instanceof FilamentUser
            ? $user->canAccessPanel($panel)
            : (config('app.env') === 'local');

        if (! $canAccess) {
            $guard->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()->to(Filament::getLoginUrl());
        }

        return $next($request);
    }
}

