<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

it('shows the sidebar with all admin sections for a logged-in Super Admin', function () {
    Role::create(['name' => 'Super Admin']);
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get(route('admin.articles.index'));

    $response->assertOk();
    $response->assertSee('Tableau de bord');
    $response->assertSee('Articles');
    $response->assertSee('Pages');
    $response->assertSee('Catégories');
    $response->assertSee('Sliders');
    $response->assertSee('News Ticker');
    $response->assertSee('Menus');
    $response->assertSee('Réglages');
});

it('marks the current section as active in the sidebar', function () {
    Role::create(['name' => 'Super Admin']);
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get(route('admin.pages.index'));

    $response->assertOk();
    $response->assertSeeInOrder(['Pages', 'bg-gray-700']);
});

it('denies sidebar access to a user without the Super Admin role', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.articles.index'));

    $response->assertForbidden();
});