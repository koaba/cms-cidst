<?php

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Hash;

it('enregistre le code de suppression hache', function () {
    $this->actingAs(adminUser())->put(route('admin.settings.update'), ['hero_title' => 'Titre', 'delete_code' => '4242'])
        ->assertRedirect(route('admin.settings.edit'))->assertSessionHasNoErrors();
    $hash = SiteSetting::current()->fresh()->delete_code_hash;
    expect($hash)->not->toBeNull()->not->toBe('4242');
    expect(Hash::check('4242', $hash))->toBeTrue();
});

it('garde le code existant quand le champ est vide', function () {
    setDeleteCode('4242');
    $this->actingAs(adminUser())->put(route('admin.settings.update'), ['hero_title' => 'Titre', 'delete_code' => ''])
        ->assertRedirect(route('admin.settings.edit'))->assertSessionHasNoErrors();
    expect(Hash::check('4242', SiteSetting::current()->fresh()->delete_code_hash))->toBeTrue();
});

it('refuse un code trop court', function () {
    $this->actingAs(adminUser())->put(route('admin.settings.update'), ['hero_title' => 'Titre', 'delete_code' => '12'])
        ->assertSessionHasErrors('delete_code');
});
