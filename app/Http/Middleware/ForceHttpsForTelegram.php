<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
class ForceHttpsForTelegram
{
    public function handle(Request $request, Closure $next)
    {
        URL::forceScheme('https');
        return $next($request);
    }
}
