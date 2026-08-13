<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            auth()->check()
            && auth()->user()->must_change_password
            && !$request->routeIs('logout', 'profile.edit', 'profile.update', 'password.update')
        ) {
            return redirect()->route('profile.edit')
                ->with('warning', 'Veuillez changer votre mot de passe avant de continuer.');
        }

        return $next($request);
    }
}
