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
