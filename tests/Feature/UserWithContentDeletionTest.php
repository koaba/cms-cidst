<?php

use App\Models\Article;
use App\Models\Page;

it('refuse de supprimer depuis l administration un compte qui possede un article', function () {
    $admin = adminUser();
    $author = publicationUser();
    $article = Article::factory()->create(['user_id' => $author->id]);

    $this->actingAs($admin)->delete(route('admin.users.destroy', $author))
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseHas('users', ['id' => $author->id]);
    $this->assertDatabaseHas('articles', ['id' => $article->id]);
});

it('refuse de supprimer depuis l administration un compte qui possede une page', function () {
    $admin = adminUser();
    $author = publicationUser();
    $page = Page::factory()->create(['user_id' => $author->id]);

    $this->actingAs($admin)->delete(route('admin.users.destroy', $author))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('users', ['id' => $author->id]);
    $this->assertDatabaseHas('pages', ['id' => $page->id]);
});

it('autorise la suppression d un compte sans contenu', function () {
    $admin = adminUser();
    $author = publicationUser();

    $this->actingAs($admin)->delete(route('admin.users.destroy', $author))
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseMissing('users', ['id' => $author->id]);
});

it('refuse l auto-suppression via le profil quand le compte possede du contenu', function () {
    adminUser();
    $author = publicationUser();
    $article = Article::factory()->create(['user_id' => $author->id]);

    $this->actingAs($author)->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertSessionHasErrors('password', errorBag: 'userDeletion');

    $this->assertDatabaseHas('users', ['id' => $author->id]);
    $this->assertDatabaseHas('articles', ['id' => $article->id]);
    $this->assertAuthenticatedAs($author);
});
