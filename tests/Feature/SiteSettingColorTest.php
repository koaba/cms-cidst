<?php

use App\Models\SiteSetting;
use App\Models\User;
use Spatie\Permission\Models\Role;

it('met à jour les couleurs du site avec des valeurs hexadécimales valides', function () {
    Role::create(['name' => 'Super Admin']);
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
        'hero_title' => 'Titre existant',
        'primary_color' => '#123ABC',
        'secondary_color' => '#000000',
    ]);

    $response->assertRedirect(route('admin.settings.edit'));
    expect(SiteSetting::current()->primary_color)->toBe('#123ABC');
    expect(SiteSetting::current()->secondary_color)->toBe('#000000');
});

it('rejette une couleur hexadécimale invalide', function () {
    Role::create(['name' => 'Super Admin']);
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
        'hero_title' => 'Titre existant',
        'primary_color' => 'not-a-color',
    ]);

    $response->assertSessionHasErrors('primary_color');
});

it('revient à la couleur par défaut quand une valeur invalide est stockée directement', function () {
    Role::create(['name' => 'Super Admin']);
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');
    SiteSetting::current()->forceFill(['primary_color' => 'javascript:alert(1)'])->saveQuietly();
    $response = $this->actingAs($admin)->get(route('admin.settings.edit'));
    $response->assertOk();
    $response->assertDontSee('javascript:alert', false);
    $response->assertSee('#000000', false);
});