<?php

use Illuminate\Support\Facades\Artisan;

function runEnvCommand(?array $probe = null): array
{
    try {
        if ($probe !== null) {
            app()->instance('App\Services\Environment\EnvironmentProbe', new class($probe)
            {
                public function __construct(private array $probe) {}

                public function collect(): array
                {
                    return $this->probe;
                }
            });
        }

        $code = Artisan::call('app:check-environment');

        return ['code' => $code, 'output' => Artisan::output()];
    } catch (Throwable) {
        return ['code' => null, 'output' => ''];
    }
}

function envCommandProbe(array $overrides = []): array
{
    return array_merge([
        'php_version' => '8.3.30',
        'extensions' => ['curl', 'dom', 'fileinfo', 'filter', 'hash', 'iconv', 'json', 'libxml', 'openssl', 'pcre', 'phar', 'session', 'simplexml', 'tokenizer', 'xml', 'xmlwriter', 'zlib', 'gd', 'pdo_mysql'],
        'gd_webp' => true,
        'disabled_functions' => [],
        'memory_limit' => '256M',
        'max_execution_time' => 60,
        'writable' => ['storage' => true, 'bootstrap/cache' => true],
        'database_ok' => true,
        'pdftoppm' => true,
        'imagick' => false,
    ], $overrides);
}

it('reussit sur l\'environnement de test reel', function () {
    $run = runEnvCommand();

    expect($run['code'])->toBe(0)
        ->and($run['output'])->toContain('PHP');
});

it('affiche OK et reussit quand tout est conforme', function () {
    $run = runEnvCommand(envCommandProbe());

    expect($run['code'])->toBe(0)
        ->and($run['output'])->toContain('OK')
        ->and($run['output'])->not->toContain('ERREUR');
});

it('affiche un avertissement sans faire echouer la commande', function () {
    $run = runEnvCommand(envCommandProbe(['pdftoppm' => false, 'imagick' => false]));

    expect($run['code'])->toBe(0)
        ->and($run['output'])->toContain('AVERTISSEMENT');
});

it('echoue avec le code 1 quand une erreur est detectee', function () {
    $run = runEnvCommand(envCommandProbe(['php_version' => '8.2.0']));

    expect($run['code'])->toBe(1)
        ->and($run['output'])->toContain('ERREUR');
});
