<?php

use App\Models\Article;
use App\Models\SiteSetting;

it('affiche la page accueil avec le titre d\'accroche des reglages', function () {
    SiteSetting::current()->update(['hero_title' => 'Titre accroche unique']);

    $this->get('/')
        ->assertOk()
        ->assertSee('Titre accroche unique');
});

it('affiche la liste du blog avec l\'article publie', function () {
    Article::factory()->create(['title' => 'Article visible dans la liste', 'is_published' => true]);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('Article visible dans la liste');
});

it('affiche la liste du blog meme sans article publie', function () {
    Article::factory()->create(['title' => 'Brouillon absent de la liste', 'is_published' => false]);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('Aucun article pour le moment.')
        ->assertDontSee('Brouillon absent de la liste');
});

it('genere un sitemap.xml valide et non vide', function () {
    Article::factory()->create(['is_published' => true]);

    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    $response->assertSee('<?xml', false);
    $response->assertSee('<urlset', false);
});
