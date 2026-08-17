<?php

use App\Models\Article;
use App\Models\Page;

it('trouve un article publié correspondant au titre', function () {
    Article::factory()->create([
        'title' => 'Colloque international sur la biodiversité',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get('/recherche?q=biodiversité');

    $response->assertOk();
    $response->assertSee('Colloque international sur la biodiversité');
});

it('n\'affiche pas les articles non publiés ou datés dans le futur dans la recherche', function () {
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

it('trouve une page publiée correspondant au titre', function () {
    Page::factory()->create([
        'title' => 'À propos du CIDST',
        'is_published' => true,
    ]);

    $response = $this->get('/recherche?q=CIDST');

    $response->assertOk();
    $response->assertSee('À propos du CIDST');
});

it('n\'affiche pas les pages non publiées dans la recherche', function () {
    Page::factory()->create([
        'title' => 'Page brouillon CIDST',
        'is_published' => false,
    ]);

    $response = $this->get('/recherche?q=CIDST');

    $response->assertOk();
    $response->assertDontSee('Page brouillon CIDST');
});

it('affiche un message d\'état vide quand la requête est vide', function () {
    $response = $this->get('/recherche');

    $response->assertOk();
    $response->assertSee('Entrez un mot-clé');
});

it('affiche un message \'aucun resultat\' quand rien ne correspond', function () {
    $response = $this->get('/recherche?q=zzzintrouvableXYZ');

    $response->assertOk();
    $response->assertSee('Aucun résultat trouvé');
});