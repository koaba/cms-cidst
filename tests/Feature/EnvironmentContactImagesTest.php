<?php

use App\Models\SiteSetting;
use App\Services\Environment\EnvironmentChecker;
use App\Services\Environment\EnvironmentProbe;

function contactEnvProbe(array $overrides = []): array
{
    return array_merge([
        'php_version' => '8.3.30',
        'extensions' => [...EnvironmentChecker::REQUIRED_EXTENSIONS, 'pdo_mysql'],
        'gd_webp' => true,
        'gd_jpeg' => true,
        'gd_png' => true,
        'disabled_functions' => [],
        'memory_limit' => '256M',
        'max_execution_time' => 60,
        'writable' => ['storage' => true, 'bootstrap/cache' => true],
        'database_ok' => true,
        'pdftoppm' => true,
        'imagick' => false,
        'contact_images_enabled' => true,
        'contact_images_max_mb' => 5,
        'contact_images_max_files' => 3,
        'upload_max_filesize' => '8M',
        'post_max_size' => '32M',
    ], $overrides);
}

function contactEnvLevel(array $overrides = []): ?string
{
    try {
        return collect((new EnvironmentChecker(contactEnvProbe($overrides)))->check())->pluck('level', 'code')->get('contact_images');
    } catch (Throwable) {
        return null;
    }
}

it('valide les limites PHP quand les images jointes sont activees', function () {
    expect(contactEnvLevel())->toBe('ok');
});

it('ne controle rien quand les images jointes sont desactivees', function () {
    expect(contactEnvLevel(['contact_images_enabled' => false, 'upload_max_filesize' => '1M', 'post_max_size' => '1M', 'gd_jpeg' => false]))->toBe('ok');
});

it('avertit quand les limites PHP ou GD sont insuffisantes', function (array $override) {
    expect(contactEnvLevel($override))->toBe('warning');
})->with([
    'upload_max_filesize trop petit' => [['upload_max_filesize' => '2M']],
    'post_max_size trop petit pour 3 images' => [['post_max_size' => '8M']],
    'GD sans JPEG' => [['gd_jpeg' => false]],
    'GD sans PNG' => [['gd_png' => false]],
    'GD sans WebP' => [['gd_webp' => false]],
]);

it('accepte un post_max_size illimite et le seuil exact', function (string $post) {
    expect(contactEnvLevel(['post_max_size' => $post, 'upload_max_filesize' => '5M']))->toBe('ok');
})->with(['illimite' => ['0'], 'seuil exact' => ['15M']]);

it('la sonde expose les reglages des images jointes et les limites PHP', function () {
    $off = (new EnvironmentProbe)->collect();

    SiteSetting::current()->update(['contact_images_enabled' => true, 'contact_images_max_mb' => 7]);
    $on = (new EnvironmentProbe)->collect();

    expect($off['contact_images_enabled'])->toBeFalse()
        ->and($on['contact_images_enabled'])->toBeTrue()
        ->and($on['contact_images_max_mb'])->toBe(7)
        ->and($on['contact_images_max_files'])->toBe((int) config('contact.max_files'))
        ->and($on['upload_max_filesize'])->toBe((string) ini_get('upload_max_filesize'))
        ->and($on['post_max_size'])->toBe((string) ini_get('post_max_size'))
        ->and($on['gd_jpeg'])->toBeBool()
        ->and($on['gd_png'])->toBeBool();
});
