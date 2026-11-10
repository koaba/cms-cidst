<?php

use App\Services\Environment\EnvironmentChecker;
use App\Services\Environment\EnvironmentProbe;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

function prodProbe(array $overrides = []): array
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
        'app_env' => 'production',
        'app_url' => 'https://cidst.example.org',
        'app_debug' => false,
        'mail_mailer' => 'smtp',
        'mail_from' => 'contact@cidst.mg',
        'queue_connection' => 'database',
        'queue_stuck' => 0,
        'now' => 1_800_000_000,
        'scheduler_heartbeat' => 1_800_000_000 - 60,
        'storage_link' => true,
        'csp_mode' => 'enforce',
    ], $overrides);
}

function prodLevels(array $overrides = []): ?array
{
    try {
        return collect((new EnvironmentChecker(prodProbe($overrides)))->check())->pluck('level', 'code')->all();
    } catch (Throwable) {
        return null;
    }
}

it('ne signale rien pour une production conforme', function () {
    $levels = prodLevels();

    expect($levels)->not->toBeNull()
        ->and(array_unique(array_values($levels)))->toBe(['ok']);
});

it('signale chaque defaut de production', function (array $override, string $code, string $level) {
    expect(prodLevels($override))->toHaveKey($code, $level);
})->with([
    'debug actif' => [['app_debug' => true], 'app_debug', 'error'],
    'mailer log' => [['mail_mailer' => 'log'], 'mail', 'warning'],
    'mailer array' => [['mail_mailer' => 'array'], 'mail', 'warning'],
    'expediteur factice' => [['mail_from' => 'hello@example.com'], 'mail', 'warning'],
    'expediteur vide' => [['mail_from' => ''], 'mail', 'warning'],
    'taches bloquees' => [['queue_stuck' => 3], 'queue', 'warning'],
    'worker non verifiable' => [['queue_connection' => 'redis', 'queue_stuck' => null], 'queue', 'warning'],
    'cron absent' => [['scheduler_heartbeat' => null], 'scheduler', 'warning'],
    'cron arrete' => [['scheduler_heartbeat' => 1_800_000_000 - 700], 'scheduler', 'warning'],
    'lien storage absent' => [['storage_link' => false], 'storage_link', 'warning'],
    'csp report-only' => [['csp_mode' => 'report-only'], 'csp', 'warning'],
    'csp desactivee' => [['csp_mode' => 'off'], 'csp', 'warning'],
]);

it('accepte le mode sync sans worker', function () {
    expect(prodLevels(['queue_connection' => 'sync', 'queue_stuck' => null]))->toHaveKey('queue', 'ok');
});

it('tolere un cron espace de 5 minutes (hebergement mutualise)', function () {
    expect(prodLevels(['scheduler_heartbeat' => 1_800_000_000 - 300]))->toHaveKey('scheduler', 'ok');
});

it('ignore ces controles hors production', function () {
    $levels = prodLevels(['app_env' => 'local', 'app_debug' => true, 'mail_mailer' => 'log', 'queue_stuck' => 9, 'scheduler_heartbeat' => null, 'storage_link' => false, 'csp_mode' => 'off']);

    expect($levels)->not->toBeNull()
        ->and(array_unique(array_values($levels)))->toBe(['ok']);
});

it('echoue avec le code 1 quand APP_DEBUG est actif en production', function () {
    app()->instance(EnvironmentProbe::class, new class(prodProbe(['app_debug' => true]))
    {
        public function __construct(private array $probe) {}

        public function collect(): array
        {
            return $this->probe;
        }
    });

    $code = Artisan::call('app:check-environment');

    expect($code)->toBe(1)->and(Artisan::output())->toContain('APP_DEBUG');
});

it('expose les reglages de production de la configuration', function () {
    config(['app.debug' => true, 'mail.default' => 'smtp', 'mail.from.address' => 'contact@cidst.mg', 'queue.default' => 'database', 'security.csp.mode' => 'enforce']);
    $collected = (new EnvironmentProbe)->collect();

    expect($collected['app_debug'] ?? null)->toBeTrue()
        ->and($collected['mail_mailer'] ?? null)->toBe('smtp')
        ->and($collected['mail_from'] ?? null)->toBe('contact@cidst.mg')
        ->and($collected['queue_connection'] ?? null)->toBe('database')
        ->and($collected['csp_mode'] ?? null)->toBe('enforce')
        ->and($collected['now'] ?? null)->toBeInt();
});

it('compte les taches en attente depuis plus de 10 minutes', function () {
    config(['queue.default' => 'database']);
    $row = fn (array $o) => array_merge(['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null, 'available_at' => time(), 'created_at' => time()], $o);

    DB::table('jobs')->insert([
        $row(['available_at' => time() - 1200]),
        $row(['available_at' => time() - 60]),
        $row(['available_at' => time() - 1200, 'reserved_at' => time() - 100]),
    ]);

    expect((new EnvironmentProbe)->collect()['queue_stuck'] ?? 'absent')->toBe(1);
});

it('ne sonde pas la file hors pilote database', function () {
    config(['queue.default' => 'sync']);
    $collected = (new EnvironmentProbe)->collect();

    expect(array_key_exists('queue_stuck', $collected))->toBeTrue()
        ->and($collected['queue_stuck'])->toBeNull();
});

it('lit le battement du planificateur dans le cache', function () {
    Cache::forever('scheduler:heartbeat', 1_700_000_000);

    expect((new EnvironmentProbe)->collect()['scheduler_heartbeat'] ?? null)->toBe(1_700_000_000);
});

it('le planificateur enregistre un battement a chaque passage', function () {
    Cache::forget('scheduler:heartbeat');
    Artisan::call('schedule:run');

    expect(Cache::get('scheduler:heartbeat'))->toBeInt();
});

it('indique si le lien public/storage existe', function () {
    expect((new EnvironmentProbe)->collect()['storage_link'] ?? null)->toBeBool();
});
