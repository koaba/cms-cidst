<?php

use App\Models\Article;

it('affiche la page accueil avec un statut 200', function () {
    $response = $this->get('/');

    $response->assertOk();
});

it('affiche la liste du blog avec un statut 200', function () {
    Article::factory()->create(['is_published' => true]);

    $response = $this->get(route('blog.index'));

    $response->assertOk();
});

it('affiche la liste du blog meme sans article publie', function () {
    Article::factory()->create(['is_published' => false]);

    $response = $this->get(route('blog.index'));

    $response->assertOk();
});

it('genere un sitemap.xml valide et non vide', function () {
    Article::factory()->create(['is_published' => true]);

    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    $response->assertSee('<?xml', false);
    $response->assertSee('<urlset', false);
});