<?php

use App\Models\SiteSetting;
use App\Models\User;
use Spatie\Permission\Models\Role;

it('updates the site colors with valid hex values', function () {
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

it('rejects an invalid hex color', function () {
    Role::create(['name' => 'Super Admin']);
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
        'hero_title' => 'Titre existant',
        'primary_color' => 'not-a-color',
    ]);

    $response->assertSessionHasErrors('primary_color');
});

it('falls back to the default color when an invalid value is stored directly', function () {
    Role::create(['name' => 'Super Admin']);
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');
    SiteSetting::current()->forceFill(['primary_color' => 'javascript:alert(1)'])->saveQuietly();
    $response = $this->actingAs($admin)->get(route('admin.settings.edit'));
    $response->assertOk();
    $response->assertDontSee('javascript:alert', false);
    $response->assertSee('#000000', false);
});