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
