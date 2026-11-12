<?php

use App\Models\SiteSetting;
use App\Services\ExistingImageConverter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// Lot 3c : le logo du menu public porte width et height (reserve sa place, evite le CLS).
// Les dimensions sont lues a l'envoi du logo, rafraichies a la conversion WebP et
// remplies par la migration pour les logos deja en place.

beforeEach(function () {
    Storage::fake('public');
    config(['media.webp.enabled' => true, 'media.webp.quality' => 82]);

    $this->saveSettings = fn (array $extra = []) => $this->actingAs(adminUser())
        ->put(route('admin.settings.update'), ['hero_title' => 'Titre', ...$extra])
        ->assertRedirect(route('admin.settings.edit'))
        ->assertSessionHasNoErrors();
});

function logoDimMigration(): object
{
    return require database_path('migrations/2026_11_12_110000_add_logo_dimensions_to_site_settings_table.php');
}

function logoDimPng(int $width, int $height): string
{
    ob_start();
    imagepng(imagecreatetruecolor($width, $height));

    return (string) ob_get_clean();
}

/** JPEG bruite : a qualite 100 il pese bien plus lourd que son equivalent WebP. */
function logoDimNoisyJpeg(int $width, int $height): string
{
    mt_srand(11);
    $image = imagecreatetruecolor($width, $height);

    for ($i = 0; $i < 400; $i++) {
        $color = imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
        imagefilledrectangle($image, mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, $width), mt_rand(0, $height), $color);
    }

    ob_start();
    imagejpeg($image, null, 100);

    return (string) ob_get_clean();
}

/** Balise img brute du menu dont l'adresse contient $needle (chaine vide si absente). */
function logoDimImgTag(string $html, string $needle): string
{
    preg_match('/<img\b[^>]*'.preg_quote($needle, '/').'[^>]*>/', $html, $match);

    return $match[0] ?? '';
}

it('enregistre les dimensions du logo envoye', function () {
    ($this->saveSettings)(['logo' => UploadedFile::fake()->image('logo.png', 300, 120)]);

    $settings = SiteSetting::current()->fresh();

    expect($settings->logo_width)->toBe(300)
        ->and($settings->logo_natural_height)->toBe(120);
});

it('remplace les dimensions quand le logo est remplace', function () {
    ($this->saveSettings)(['logo' => UploadedFile::fake()->image('ancien.png', 300, 120)]);
    ($this->saveSettings)(['logo' => UploadedFile::fake()->image('nouveau.png', 200, 200)]);

    $settings = SiteSetting::current()->fresh();

    expect($settings->logo_width)->toBe(200)
        ->and($settings->logo_natural_height)->toBe(200);
});

it('garde les dimensions quand aucun logo n est envoye', function () {
    ($this->saveSettings)(['logo' => UploadedFile::fake()->image('logo.png', 300, 120)]);
    ($this->saveSettings)();

    $settings = SiteSetting::current()->fresh();

    expect($settings->logo_width)->toBe(300)
        ->and($settings->logo_natural_height)->toBe(120);
});

it('mesure le logo apres sa conversion en WebP', function () {
    $file = UploadedFile::fake()->createWithContent('logo.jpg', logoDimNoisyJpeg(600, 400));

    ($this->saveSettings)(['logo' => $file]);

    $settings = SiteSetting::current()->fresh();

    expect($settings->logo_path)->toEndWith('.webp')
        ->and($settings->logo_width)->toBe(600)
        ->and($settings->logo_natural_height)->toBe(400);
});

it('rafraichit les dimensions du logo quand une image existante est convertie', function () {
    Storage::disk('public')->put('logos/ancien.jpg', logoDimNoisyJpeg(600, 400));
    SiteSetting::query()->create(['logo_path' => 'logos/ancien.jpg', 'logo_width' => 1, 'logo_natural_height' => 1]);

    $new = app(ExistingImageConverter::class)->convert('logos/ancien.jpg');

    $settings = SiteSetting::current()->fresh();

    expect($new)->toBe('logos/ancien.webp')
        ->and($settings->logo_path)->toBe('logos/ancien.webp')
        ->and($settings->logo_width)->toBe(600)
        ->and($settings->logo_natural_height)->toBe(400);
});

it('ecrit width et height sur le logo personnalise du menu public', function () {
    SiteSetting::current()->update([
        'logo_path' => 'logos/cidst.webp',
        'logo_width' => 300,
        'logo_natural_height' => 120,
    ]);

    $tag = logoDimImgTag($this->get('/')->assertOk()->getContent(), 'logos/cidst.webp');

    expect($tag)->toContain('width="300" height="120"');
});

it('n ecrit ni width ni height quand les dimensions du logo sont inconnues', function () {
    SiteSetting::current()->update(['logo_path' => 'logos/cidst.webp']);

    $tag = logoDimImgTag($this->get('/')->assertOk()->getContent(), 'logos/cidst.webp');

    expect($tag)->toContain('logos/cidst.webp')
        ->and($tag)->not->toContain('width=')
        ->and($tag)->not->toContain('height="')
        ->and($tag)->toContain('height: 48px');
});

it('garde les vraies dimensions sur le logo par defaut du menu public', function () {
    [$width, $height] = getimagesize(public_path('images/logo-cidst.png'));

    $tag = logoDimImgTag($this->get('/')->assertOk()->getContent(), 'images/logo-cidst.png');

    expect($tag)->toContain('width="'.$width.'" height="'.$height.'"');
});

it('remplit les dimensions des logos existants', function () {
    Storage::disk('public')->put('logos/a.png', logoDimPng(200, 80));
    SiteSetting::query()->create(['logo_path' => 'logos/a.png']);
    SiteSetting::current();

    logoDimMigration()->backfill();

    $settings = SiteSetting::current();

    expect($settings->logo_width)->toBe(200)
        ->and($settings->logo_natural_height)->toBe(80);
});

it('laisse les dimensions vides quand le fichier du logo est absent', function () {
    SiteSetting::query()->create(['logo_path' => 'logos/absent.png']);

    logoDimMigration()->backfill();

    $settings = SiteSetting::current()->fresh();

    expect($settings->logo_width)->toBeNull()
        ->and($settings->logo_natural_height)->toBeNull();
});

it('ne touche pas aux dimensions deja connues', function () {
    Storage::disk('public')->put('logos/a.png', logoDimPng(200, 80));
    SiteSetting::query()->create(['logo_path' => 'logos/a.png', 'logo_width' => 10, 'logo_natural_height' => 5]);

    logoDimMigration()->backfill();

    $settings = SiteSetting::current()->fresh();

    expect($settings->logo_width)->toBe(10)
        ->and($settings->logo_natural_height)->toBe(5);
});
