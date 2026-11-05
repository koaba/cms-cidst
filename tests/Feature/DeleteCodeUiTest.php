<?php

use App\Models\Article;
use App\Models\Page;

it('ajoute le champ code aux suppressions pour Publication', function () {
    Article::factory()->create();
    Page::factory()->create();
    $user = publicationUser();
    foreach (['admin.articles.index', 'admin.pages.index'] as $r) {
        $this->actingAs($user)->get(route($r))->assertOk()->assertSee('name="delete_code"', false);
    }
});

it('ne demande pas le code au Super Admin', function () {
    Article::factory()->create();
    $this->actingAs(adminUser())->get(route('admin.articles.index'))->assertOk()->assertDontSee('name="delete_code"', false);
});

it('propose le champ code dans les reglages', function () {
    $this->actingAs(adminUser())->get(route('admin.settings.edit'))->assertOk()->assertSee('name="delete_code"', false);
});

it('rend les suppressions des listes par le composant delete-form', function (string $route, string $factory, string $message) {
    $factory::factory()->create();

    $this->actingAs(publicationUser())->get(route($route))->assertOk()
        ->assertSee($message, false)
        ->assertSee('this.delete_code.value = prompt(', false)
        ->assertDontSee('!this.delete_code', false)
        ->assertDontSee('this.form.delete_code', false);
})->with([
    'articles' => ['admin.articles.index', Article::class, 'Supprimer cet article ?'],
    'pages' => ['admin.pages.index', Page::class, 'Supprimer cette page ?'],
]);
