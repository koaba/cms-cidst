<?php

use App\Models\Article;
use App\Models\Page;

it('finds a published article matching the title', function () {
    Article::factory()->create([
        'title' => 'Colloque international sur la biodiversité',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get('/recherche?q=biodiversité');

    $response->assertOk();
    $response->assertSee('Colloque international sur la biodiversité');
});

it('does not show unpublished or future-dated articles in search', function () {
    Article::factory()->create([
        'title' => 'Article brouillon biodiversité',
        'is_published' => false,
        'published_at' => now()->subDay(),
    ]);

    Article::factory()->create([
        'title' => 'Article futur biodiversité',
        'is_published' => true,
        'published_at' => now()->addDay(),
    ]);

    $response = $this->get('/recherche?q=biodiversité');

    $response->assertOk();
    $response->assertDontSee('Article brouillon biodiversité');
    $response->assertDontSee('Article futur biodiversité');
});

it('finds a published page matching the title', function () {
    Page::factory()->create([
        'title' => 'À propos du CIDST',
        'is_published' => true,
    ]);

    $response = $this->get('/recherche?q=CIDST');

    $response->assertOk();
    $response->assertSee('À propos du CIDST');
});

it('does not show unpublished pages in search', function () {
    Page::factory()->create([
        'title' => 'Page brouillon CIDST',
        'is_published' => false,
    ]);

    $response = $this->get('/recherche?q=CIDST');

    $response->assertOk();
    $response->assertDontSee('Page brouillon CIDST');
});

it('shows an empty state message when the query is empty', function () {
    $response = $this->get('/recherche');

    $response->assertOk();
    $response->assertSee('Entrez un mot-clé');
});

it('shows a no-results message when nothing matches', function () {
    $response = $this->get('/recherche?q=zzzintrouvableXYZ');

    $response->assertOk();
    $response->assertSee('Aucun résultat trouvé');
});