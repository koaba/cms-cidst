<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ConfirmDeleteCode
{
    private const MAX_FAILURES = 5;

    private const DECAY_SECONDS = 900;

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->hasRole(UserRole::SuperAdmin)) {
            return $next($request);
        }

        $key = 'confirm-delete:'.($request->user()?->getAuthIdentifier() ?? $request->ip());

        if (RateLimiter::tooManyAttempts($key, self::MAX_FAILURES)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            return back()->withErrors(['delete_code' => "Trop de tentatives. Reessayez dans {$minutes} minute(s)."]);
        }

        $hash = SiteSetting::current()->delete_code_hash;
        $code = (string) $request->input('delete_code', '');

        if (! $hash || $code === '' || ! Hash::check($code, $hash)) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            return back()->withErrors(['delete_code' => 'Code de suppression absent ou incorrect.']);
        }

        RateLimiter::clear($key);

        return $next($request);
    }
}
