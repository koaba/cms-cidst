<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class ConfirmDeleteCode
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->hasRole('Super Admin')) {
            return $next($request);
        }

        $hash = SiteSetting::current()->delete_code_hash;
        $code = (string) $request->input('delete_code', '');

        if (! $hash || $code === '' || ! Hash::check($code, $hash)) {
            return back()->withErrors(['delete_code' => 'Code de suppression absent ou incorrect.']);
        }

        return $next($request);
    }
}
