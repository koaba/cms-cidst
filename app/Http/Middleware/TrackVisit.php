<?php

namespace App\Http\Middleware;

use App\Models\SiteVisit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Compte une visite anonyme sur les pages publiques (liste blanche de routes).
 *
 * Vie privee : ni IP ni navigateur ne sont stockes. Le visiteur est represente par
 * un hash (IP + navigateur + date du jour + APP_KEY) qui change chaque jour, donc
 * impossible de suivre une personne d'un jour a l'autre. Le chemin est enregistre
 * sans sa query string (une recherche ne laisse aucune trace).
 */
class TrackVisit
{
    private const TRACKED_ROUTES = ['home', 'search', 'blog.*', 'pages.*', 'documents.*', 'sliders.index', 'contact.show'];

    private const BOT_PATTERN = '/bot|crawl|spider|slurp|curl|wget|python|headless|preview|monitor/i';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $agent = (string) $request->userAgent();

        if (! $request->isMethod('GET') || ! $response->isSuccessful() || $this->isBot($agent) || ! $this->isTracked($request)) {
            return $response;
        }

        try {
            $this->record($request, $agent);
        } catch (Throwable $e) {
            report($e);
        }

        return $response;
    }

    private function isBot(string $agent): bool
    {
        return (bool) preg_match(self::BOT_PATTERN, $agent);
    }

    private function isTracked(Request $request): bool
    {
        return Str::is(self::TRACKED_ROUTES, (string) $request->route()?->getName());
    }

    private function record(Request $request, string $agent): void
    {
        SiteVisit::create([
            'path' => Str::limit('/'.ltrim($request->path(), '/'), 255, ''),
            'visitor_hash' => hash('sha256', $request->ip().'|'.$agent.'|'.now()->toDateString().'|'.config('app.key')),
            'referrer_host' => $this->referrerHost($request),
            'device' => preg_match('/Mobile|Android|iPhone|iPad/i', $agent) ? 'mobile' : 'desktop',
            'visited_at' => now(),
        ]);
    }

    private function referrerHost(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        return is_string($host) && $host !== $request->getHost() ? Str::limit($host, 255, '') : null;
    }
}
