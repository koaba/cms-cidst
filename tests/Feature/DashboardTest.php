<?php

use App\Models\Article;
use App\Models\User;
use Spatie\Permission\Models\Role;

it('affiche le tableau de bord sans erreur SQL (régression DATE_FORMAT/SQLite)', function () {
    Role::create(['name' => 'Super Admin']);
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    Article::factory()->create([
        'is_published' => true,
        'published_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
});

it('calcule correctement le nombre d\'articles publiés par mois', function () {
    Role::create(['name' => 'Super Admin']);
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    Article::factory()->create(['is_published' => true, 'published_at' => now()]);
    Article::factory()->create(['is_published' => true, 'published_at' => now()]);
    Article::factory()->create(['is_published' => true, 'published_at' => now()->subMonths(2)]);
    // Ne doit pas être compté : non publié
    Article::factory()->create(['is_published' => false, 'published_at' => now()]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $moisActuel = now()->format('Y-m');
    $response->assertViewHas('activiteMensuelle', function ($activite) use ($moisActuel) {
        return $activite->get($moisActuel) === 2;
    });
});

it('refuse l\'accès au dashboard à un utilisateur sans rôle autorisé', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertForbidden();
});