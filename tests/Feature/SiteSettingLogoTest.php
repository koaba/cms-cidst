<?php

use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->saveSettings = fn (array $extra = []) => $this->actingAs(adminUser())
        ->put(route('admin.settings.update'), ['hero_title' => 'Titre', ...$extra])
        ->assertRedirect(route('admin.settings.edit'))
        ->assertSessionHasNoErrors();
});

it('enregistre le logo envoye', function () {
    ($this->saveSettings)(['logo' => UploadedFile::fake()->image('logo.png')]);

    $path = SiteSetting::current()->fresh()->logo_path;

    expect($path)->toStartWith('logos/');
    Storage::disk('public')->assertExists($path);
});

it('remplace l ancien logo et supprime son fichier', function () {
    ($this->saveSettings)(['logo' => UploadedFile::fake()->image('ancien.png')]);
    $old = SiteSetting::current()->fresh()->logo_path;

    ($this->saveSettings)(['logo' => UploadedFile::fake()->image('nouveau.png')]);
    $new = SiteSetting::current()->fresh()->logo_path;

    expect($new)->not->toBe($old);
    Storage::disk('public')->assertMissing($old);
    Storage::disk('public')->assertExists($new);
});

it('garde le logo existant quand aucun fichier n est envoye', function () {
    ($this->saveSettings)(['logo' => UploadedFile::fake()->image('logo.png')]);
    $path = SiteSetting::current()->fresh()->logo_path;

    ($this->saveSettings)();

    expect(SiteSetting::current()->fresh()->logo_path)->toBe($path);
    Storage::disk('public')->assertExists($path);
});

it('refuse un fichier qui n est pas une image', function () {
    $this->actingAs(adminUser())
        ->put(route('admin.settings.update'), ['hero_title' => 'Titre', 'logo' => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf')])
        ->assertSessionHasErrors('logo');

    expect(SiteSetting::current()->fresh()->logo_path)->toBeNull();
});

/** JPEG bruite : a qualite 100 il pese bien plus lourd que son equivalent WebP. */
function logoNoisyJpegBytes(): string
{
    mt_srand(11);
    $image = imagecreatetruecolor(600, 400);

    for ($i = 0; $i < 400; $i++) {
        $color = imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
        imagefilledrectangle($image, mt_rand(0, 600), mt_rand(0, 400), mt_rand(0, 600), mt_rand(0, 400), $color);
    }

    ob_start();
    imagejpeg($image, null, 100);

    return (string) ob_get_clean();
}

it('convertit un logo JPEG en WebP et ne garde pas l original', function () {
    config(['media.webp.enabled' => true]);
    $file = UploadedFile::fake()->createWithContent('logo.jpg', logoNoisyJpegBytes());

    ($this->saveSettings)(['logo' => $file]);

    $path = SiteSetting::current()->fresh()->logo_path;

    expect($path)->toStartWith('logos/')->toEndWith('.webp')
        ->and(Storage::disk('public')->files('logos'))->toHaveCount(1);
    Storage::disk('public')->assertExists($path);
});

it('garde le logo tel quel quand la conversion WebP est coupee', function () {
    config(['media.webp.enabled' => false]);
    $file = UploadedFile::fake()->createWithContent('logo.jpg', logoNoisyJpegBytes());

    ($this->saveSettings)(['logo' => $file]);

    $path = SiteSetting::current()->fresh()->logo_path;

    expect($path)->not->toEndWith('.webp');
    Storage::disk('public')->assertExists($path);
});

it('refuse un logo GIF', function () {
    $this->actingAs(adminUser())
        ->put(route('admin.settings.update'), ['hero_title' => 'Titre', 'logo' => UploadedFile::fake()->image('logo.gif')])
        ->assertSessionHasErrors('logo');

    expect(SiteSetting::current()->fresh()->logo_path)->toBeNull();
});

it('a une hauteur de logo de 48 px par defaut', function () {
    expect(SiteSetting::current()->fresh()->logo_height)->toBe(48);
});

it('enregistre la hauteur du logo', function () {
    ($this->saveSettings)(['logo_height' => 64]);

    expect(SiteSetting::current()->fresh()->logo_height)->toBe(64);
});

it('refuse une hauteur de logo hors limites', function (mixed $value) {
    $this->actingAs(adminUser())
        ->put(route('admin.settings.update'), ['hero_title' => 'Titre', 'logo_height' => $value])
        ->assertSessionHasErrors('logo_height');

    expect(SiteSetting::current()->fresh()->logo_height)->toBe(48);
})->with([10, 100, 'abc']);

it('garde la hauteur du logo quand le champ est vide', function () {
    ($this->saveSettings)(['logo_height' => 60]);
    ($this->saveSettings)(['logo_height' => '']);

    expect(SiteSetting::current()->fresh()->logo_height)->toBe(60);
});

it('applique la hauteur au logo personnalise du menu public', function () {
    ($this->saveSettings)(['logo_height' => 64]);
    SiteSetting::current()->update(['logo_path' => 'logos/cidst.webp']);

    $this->get('/')->assertOk()
        ->assertSee(asset('storage/logos/cidst.webp'), false)
        ->assertSee('height: 64px', false);
});

it('applique la hauteur au logo par defaut du menu public', function () {
    ($this->saveSettings)(['logo_height' => 56]);

    $this->get('/')->assertOk()
        ->assertSee(asset('images/logo-cidst.png'), false)
        ->assertSee('height: 56px', false);
});
