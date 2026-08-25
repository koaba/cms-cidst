<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'force.password.change' => \App\Http\Middleware\ForcePasswordChange::class,
            'track.view' => \App\Http\Middleware\TrackPageView::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\ForcePasswordChange::class,
        ]);
        // Fait confiance aux en-têtes standards de proxy (X-Forwarded-For, etc.)
        // venant de n'importe quelle IP amont. Nécessaire pour récupérer la
        // vraie IP du visiteur si le site est un jour derrière un proxy/CDN
        // (Cloudflare, load balancer...). Sans danger en accès direct : si
        // aucun proxy n'est présent, ces en-têtes sont simplement absents et
        // Laravel utilise l'IP de connexion normale.
        // $middleware->trustProxies(at: '*');
// À réactiver avec les IPs précises du proxy/CDN le jour où l'infra
// de production en dispose réellement (voir doc Laravel trustProxies()).
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();