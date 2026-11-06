<?php

use App\Models\Article;
use App\Models\Page;

it('masque une page programmee dans le futur dans la recherche', function () {
    Page::factory()->create([
        'title' => 'Page programmee zeta',
        'is_published' => true,
        'published_at' => now()->addDay(),
    ]);

    $this->get('/recherche?q=zeta')->assertOk()->assertDontSee('Page programmee zeta');
});

it('affiche une page publiee dont la date de publication est passee', function () {
    Page::factory()->create([
        'title' => 'Page passee sigma',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $this->get('/recherche?q=sigma')->assertOk()->assertSee('Page passee sigma');
});

it('affiche un article publie sans date de publication', function () {
    Article::factory()->create([
        'title' => 'Article sans date omega',
        'is_published' => true,
        'published_at' => null,
    ]);

    $this->get('/recherche?q=omega')->assertOk()->assertSee('Article sans date omega');
});
