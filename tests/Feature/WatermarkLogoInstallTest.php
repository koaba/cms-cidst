<?php

use App\Services\Environment\EnvironmentChecker;
use App\Services\Environment\EnvironmentProbe;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('installe le logo de filigrane quand il est absent', function () {
    $this->artisan('watermark:install-logo')->assertSuccessful();

    Storage::disk('public')->assertExists(config('watermark.logo_path'));
    expect(Storage::disk('public')->get(config('watermark.logo_path')))
        ->toBe(file_get_contents(public_path(config('watermark.default_logo'))));
});

it('ne remplace pas un logo de filigrane deja present', function () {
    Storage::disk('public')->put(config('watermark.logo_path'), 'logo-personnalise');

    $this->artisan('watermark:install-logo')->assertSuccessful();

    expect(Storage::disk('public')->get(config('watermark.logo_path')))->toBe('logo-personnalise');
});

it('remplace le logo de filigrane avec --force', function () {
    Storage::disk('public')->put(config('watermark.logo_path'), 'logo-personnalise');

    $this->artisan('watermark:install-logo', ['--force' => true])->assertSuccessful();

    expect(Storage::disk('public')->get(config('watermark.logo_path')))->not->toBe('logo-personnalise');
});

it('echoue quand le logo source est introuvable', function () {
    config(['watermark.default_logo' => 'images/inexistant.png']);

    $this->artisan('watermark:install-logo')->assertFailed();

    Storage::disk('public')->assertMissing(config('watermark.logo_path'));
});

it('signale l absence du logo de filigrane dans le controle d environnement', function () {
    $levels = fn (bool $present) => collect((new EnvironmentChecker(['watermark_logo' => $present]))->check())
        ->pluck('level', 'code')->all();

    expect($levels(false))->toHaveKey('watermark_logo', 'warning')
        ->and($levels(true))->toHaveKey('watermark_logo', 'ok');
});

it('sonde la presence reelle du logo de filigrane', function () {
    expect((new EnvironmentProbe)->collect()['watermark_logo'] ?? null)->toBeFalse();

    Storage::disk('public')->put(config('watermark.logo_path'), 'x');

    expect((new EnvironmentProbe)->collect()['watermark_logo'] ?? null)->toBeTrue();
});
