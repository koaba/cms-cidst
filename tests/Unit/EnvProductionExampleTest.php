<?php

use App\Services\Environment\EnvironmentChecker;
use Dotenv\Dotenv;

function productionExample(): array
{
    $path = dirname(__DIR__, 2).'/.env.production.example';

    return file_exists($path) ? Dotenv::parse((string) file_get_contents($path)) : [];
}

it('fournit un modele d environnement de production', function () {
    expect(productionExample())->not->toBeEmpty();
});

it('ne produit aucun probleme sur les controles de production', function () {
    $env = productionExample();
    expect($env)->not->toBeEmpty();

    $probe = [
        'app_env' => $env['APP_ENV'] ?? null,
        'app_debug' => filter_var($env['APP_DEBUG'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
        'app_url' => $env['APP_URL'] ?? '',
        'mail_mailer' => $env['MAIL_MAILER'] ?? '',
        'mail_from' => $env['MAIL_FROM_ADDRESS'] ?? '',
        'queue_connection' => $env['QUEUE_CONNECTION'] ?? '',
        'csp_mode' => $env['CSP_MODE'] ?? '',
    ];

    $results = collect((new EnvironmentChecker($probe))->check())
        ->whereIn('code', ['app_debug', 'mail', 'app_url', 'csp', 'queue']);

    expect($results)->toHaveCount(5);
    expect($results->where('level', '!=', 'ok')->pluck('message')->all())->toBe([]);
});
