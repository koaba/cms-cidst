<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

it('affiche la barre latérale avec toutes les sections admin pour un Super Admin connecté', function () {
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

it('marque la section courante comme active dans la barre latérale', function () {
    Role::create(['name' => 'Super Admin']);
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get(route('admin.pages.index'));

    $response->assertOk();
    $response->assertSeeInOrder(['Pages', 'bg-gray-700']);
});

it('refuse l\'accès à la barre latérale à un utilisateur sans le rôle Super Admin', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.articles.index'));

    $response->assertForbidden();
});