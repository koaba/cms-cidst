<?php

use App\Models\Article;
use App\Models\SiteSetting;

it('affiche les articles publiés récents dans la sidebar', function () {
    Article::factory()->create([
        'title' => 'Article visible en sidebar',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get('/pages');

    $response->assertOk();
    $response->assertSee('Article visible en sidebar');
});

it('n\'affiche pas les articles non publiés dans la sidebar', function () {
    Article::factory()->create([
        'title' => 'Article brouillon sidebar',
        'is_published' => false,
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get('/pages');

    $response->assertOk();
    $response->assertDontSee('Article brouillon sidebar');
});

it('limite la sidebar à 5 articles', function () {
    Article::factory()->count(8)->create([
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get('/pages');

    $response->assertOk();
    $response->assertSee('Actualités récentes');
});

it('affiche le lien Facebook quand facebook_url est renseignée', function () {
    SiteSetting::current()->update(['facebook_url' => 'https://facebook.com/cidst']);

    $response = $this->get('/pages');

    $response->assertOk();
    $response->assertSee('https://facebook.com/cidst', false);
});

it('n\'affiche pas de lien Facebook quand facebook_url est vide', function () {
    SiteSetting::current()->update(['facebook_url' => null]);

    $response = $this->get('/pages');

    $response->assertOk();
    $response->assertDontSee('Suivez le CIDST sur Facebook');
});

it('affiche un état vide propre quand aucune actualité n\'existe', function () {
    $response = $this->get('/pages');

    $response->assertOk();
    $response->assertSee('Aucune actualité pour le moment');
});