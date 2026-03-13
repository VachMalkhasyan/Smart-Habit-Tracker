<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectIfNotOnboarded
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (
            $user &&
            !$user->onboarding_completed &&
            !$request->routeIs('onboarding*') &&
            !$request->routeIs('logout') &&
            !$request->routeIs('verification*')
        ) {
            return redirect()->route('onboarding');
        }

        return $next($request);
    }
}
