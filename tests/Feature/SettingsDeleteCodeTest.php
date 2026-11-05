<?php

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Hash;

it('enregistre le code de suppression hache', function () {
    $this->actingAs(adminUser())->put(route('admin.settings.update'), ['hero_title' => 'Titre', 'delete_code' => '12345678'])
        ->assertRedirect(route('admin.settings.edit'))->assertSessionHasNoErrors();
    $hash = SiteSetting::current()->fresh()->delete_code_hash;
    expect($hash)->not->toBeNull()->not->toBe('12345678');
    expect(Hash::check('12345678', $hash))->toBeTrue();
});

it('garde le code existant quand le champ est vide', function () {
    setDeleteCode('4242');
    $this->actingAs(adminUser())->put(route('admin.settings.update'), ['hero_title' => 'Titre', 'delete_code' => ''])
        ->assertRedirect(route('admin.settings.edit'))->assertSessionHasNoErrors();
    expect(Hash::check('4242', SiteSetting::current()->fresh()->delete_code_hash))->toBeTrue();
});

it('refuse un code de moins de 8 caracteres', function (string $code) {
    $this->actingAs(adminUser())->put(route('admin.settings.update'), ['hero_title' => 'Titre', 'delete_code' => $code])
        ->assertSessionHasErrors('delete_code');
    expect(SiteSetting::current()->fresh()->delete_code_hash)->toBeNull();
})->with([
    '2 caracteres' => '12',
    '7 caracteres' => '1234567',
]);

it('annonce 8 caracteres minimum dans le formulaire', function () {
    $html = $this->actingAs(adminUser())->get(route('admin.settings.edit'))->assertOk()->getContent();

    expect($html)->toContain('minlength="8"');
});
