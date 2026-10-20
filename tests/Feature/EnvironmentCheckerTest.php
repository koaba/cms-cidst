<?php

use App\Services\Environment\EnvironmentChecker;

function envProbe(array $overrides = []): array
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

function envLevels(array $overrides = []): ?array
{
    try {
        $checker = new EnvironmentChecker(envProbe($overrides));

        return collect($checker->check())->pluck('level', 'code')->all();
    } catch (Throwable) {
        return null;
    }
}

it('ne signale rien pour un environnement conforme', function () {
    $levels = envLevels();

    expect($levels)->not->toBeNull()
        ->and(array_unique(array_values($levels)))->toBe(['ok']);
});

it('refuse une version de PHP trop ancienne', function () {
    expect(envLevels(['php_version' => '8.2.0']))->toHaveKey('php_version', 'error');
});

it('refuse GD sans WebP', function () {
    expect(envLevels(['gd_webp' => false]))->toHaveKey('gd_webp', 'error');
});

it('refuse une extension requise manquante', function () {
    expect(envLevels(['extensions' => ['curl']]))->toHaveKey('extensions', 'error');
});

it('refuse un dossier non inscriptible', function () {
    expect(envLevels(['writable' => ['storage' => false, 'bootstrap/cache' => true]]))->toHaveKey('writable', 'error');
});

it('refuse une base inaccessible', function () {
    expect(envLevels(['database_ok' => false]))->toHaveKey('database', 'error');
});

it('avertit quand proc_open est desactive', function () {
    expect(envLevels(['disabled_functions' => ['proc_open']]))->toHaveKey('proc_open', 'warning');
});

it('avertit quand ni pdftoppm ni Imagick ne sont disponibles', function () {
    expect(envLevels(['pdftoppm' => false, 'imagick' => false]))->toHaveKey('pdf_thumbnail', 'warning');
});

it('accepte Imagick seul pour les miniatures PDF', function () {
    expect(envLevels(['pdftoppm' => false, 'imagick' => true]))->toHaveKey('pdf_thumbnail', 'ok');
});

it('avertit quand memory_limit est inferieure a 128M', function () {
    expect(envLevels(['memory_limit' => '64M']))->toHaveKey('memory_limit', 'warning');
});

it('accepte une memory_limit illimitee', function () {
    expect(envLevels(['memory_limit' => '-1']))->toHaveKey('memory_limit', 'ok');
});

it('avertit quand max_execution_time est inferieur a 30 s', function () {
    expect(envLevels(['max_execution_time' => 10]))->toHaveKey('max_execution_time', 'warning');
});
