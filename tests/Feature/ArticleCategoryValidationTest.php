<?php

use App\Models\Category;
use App\Models\User;
use Spatie\Permission\Models\Role;

it('refuse la création d\'un article avec un ID de catégorie inexistant', function () {
    Role::create(['name' => 'Super Admin']);
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    $response = $this->actingAs($user)->post('/admin/articles', [
        'title' => 'Test article',
        'content' => 'Contenu test',
        'categories' => [9999],
    ]);

    $response->assertSessionHasErrors('categories.0');
    $this->assertDatabaseMissing('articles', ['title' => 'Test article']);
});

it('accepte la création d\'un article avec des IDs de catégories valides', function () {
    Role::create(['name' => 'Super Admin']);
    $user = User::factory()->create();
    $user->assignRole('Super Admin');
    $category = Category::create(['name' => 'Sciences', 'slug' => 'sciences']);

    $response = $this->actingAs($user)->post('/admin/articles', [
        'title' => 'Test article valide',
        'content' => 'Contenu test',
        'categories' => [$category->id],
    ]);

    $response->assertSessionDoesntHaveErrors();
    $this->assertDatabaseHas('articles', ['title' => 'Test article valide']);
});

it('refuse la modification d\'un article avec un ID de catégorie inexistant', function () {
    Role::create(['name' => 'Super Admin']);
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    $article = \App\Models\Article::create([
        'title' => 'Article existant',
        'slug' => 'article-existant',
        'content' => 'Contenu',
        'user_id' => $user->id,
        'is_published' => true,
        'published_at' => now(),
    ]);

    $response = $this->actingAs($user)->put("/admin/articles/{$article->id}", [
        'title' => 'Article existant modifié',
        'content' => 'Contenu',
        'categories' => [9999],
    ]);

    $response->assertSessionHasErrors('categories.0');
});