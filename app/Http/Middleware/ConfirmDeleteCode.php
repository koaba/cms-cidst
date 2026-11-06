<?php

namespace App\Http\Middleware;

use App\Services\DeleteCodeVerifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfirmDeleteCode
{
    public function __construct(private readonly DeleteCodeVerifier $verifier) {}

    public function handle(Request $request, Closure $next): Response
    {
        $error = $this->verifier->check($request);

        if ($error !== null) {
            return back()->withErrors(['delete_code' => $error]);
        }

        return $next($request);
    }
}
