<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePanelAccessIsValid
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $panel = Filament::getCurrentPanel();
        $user = Auth::user();

        if ($panel && method_exists($user, 'canAccessPanel') && ! $user->canAccessPanel($panel)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if (method_exists($panel, 'getLoginUrl')) {
                return redirect()->to($panel->getLoginUrl());
            }

            $panelPath = trim((string) $panel->getPath(), '/');

            return redirect()->to(url($panelPath !== '' ? "{$panelPath}/login" : 'login'));
        }

        return $next($request);
    }
}
