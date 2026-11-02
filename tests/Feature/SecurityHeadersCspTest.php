<?php

use App\Support\VideoEmbed;

it('envoie le CSP bloquant en mode enforce', function () {
    config(['security.csp.mode' => 'enforce']);

    $response = $this->get('/login');

    $response->assertHeader('Content-Security-Policy');
    expect($response->headers->has('Content-Security-Policy-Report-Only'))->toBeFalse();
});

it('envoie le CSP en observation seule en mode report-only', function () {
    config(['security.csp.mode' => 'report-only']);

    $response = $this->get('/login');

    $response->assertHeader('Content-Security-Policy-Report-Only');
    expect($response->headers->has('Content-Security-Policy'))->toBeFalse();
});

it('n\'envoie aucun CSP quand il est desactive', function () {
    config(['security.csp.mode' => 'off']);

    $response = $this->get('/login');

    expect($response->headers->has('Content-Security-Policy'))->toBeFalse()
        ->and($response->headers->has('Content-Security-Policy-Report-Only'))->toBeFalse();
});

it('limite frame-src aux seuls hotes de video autorises', function () {
    config(['security.csp.mode' => 'enforce']);

    $csp = $this->get('/login')->headers->get('Content-Security-Policy');
    $directive = collect(explode('; ', $csp))->first(fn ($d) => str_starts_with($d, 'frame-src '));

    expect($directive)->toBe('frame-src https://www.youtube.com https://player.vimeo.com');
});

it('interdit les objets, la base externe et l\'embarquement par un autre site', function () {
    config(['security.csp.mode' => 'enforce']);

    $csp = $this->get('/login')->headers->get('Content-Security-Policy');
    $directives = explode('; ', $csp);

    expect($directives)->toContain("object-src 'none'")
        ->and($directives)->toContain("base-uri 'self'")
        ->and($directives)->toContain("form-action 'self'")
        ->and($directives)->toContain("frame-ancestors 'self'");
});

it('autorise dans frame-src chaque hote produit par VideoEmbed', function (string $url) {
    $embed = VideoEmbed::embedUrl($url);
    $origin = parse_url($embed, PHP_URL_SCHEME).'://'.parse_url($embed, PHP_URL_HOST);

    expect(config('security.csp.directives.frame-src'))->toContain($origin);
})->with([
    'youtube' => ['https://youtu.be/dQw4w9WgXcQ'],
    'vimeo' => ['https://vimeo.com/76979871'],
]);

it('se desactive quand le serveur Vite de developpement tourne', function () {
    $hot = public_path('hot');

    if (file_exists($hot)) {
        $this->markTestSkipped('public/hot existe deja : npm run dev est en cours.');
    }

    config(['security.csp.mode' => 'enforce']);
    $this->app['env'] = 'local';
    file_put_contents($hot, 'http://localhost:5173');

    try {
        $response = $this->get('/login');
    } finally {
        unlink($hot);
    }

    expect($response->headers->has('Content-Security-Policy'))->toBeFalse();
});
