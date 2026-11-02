<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        $this->applyContentSecurityPolicy($response);

        return $response;
    }

    private function applyContentSecurityPolicy(Response $response): void
    {
        $mode = config('security.csp.mode');

        if (! in_array($mode, ['enforce', 'report-only'], true)) {
            return;
        }

        // Serveur Vite de developpement (public/hot) : scripts et styles servis depuis un autre hote.
        if (app()->isLocal() && file_exists(public_path('hot'))) {
            return;
        }

        $policy = collect(config('security.csp.directives'))
            ->map(fn (array $sources, string $directive) => trim($directive.' '.implode(' ', $sources)))
            ->implode('; ');

        $header = $mode === 'enforce' ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only';

        $response->headers->set($header, $policy);
    }
}
