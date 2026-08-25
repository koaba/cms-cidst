<?php

use App\Models\Article;

it('affiche une page article publique avec un statut 200', function () {
    $article = Article::factory()->create([
        'title' => 'Article de test SEO',
        'is_published' => true,
    ]);

    $response = $this->get(route('blog.show', $article->slug));

    $response->assertOk();
});

it('affiche le titre de l\'article dans le contenu HTML', function () {
    $article = Article::factory()->create([
        'title' => 'Titre unique visible',
        'is_published' => true,
    ]);

    $response = $this->get(route('blog.show', $article->slug));

    $response->assertSee('Titre unique visible');
});

it('genere les meta SEO sans erreur fatale (regression noIndex)', function () {
    // Ce test couvre specifiquement le bug historique :
    // SeoService::noIndex() avait un corps vide avec ": bool" declare,
    // provoquant une TypeError fatale a CHAQUE affichage de page publique.
    // Aucun des 136 tests existants ne l'avait detecte car aucun n'exercait
    // le rendu reel d'une page publique.
    $article = Article::factory()->create([
        'is_published' => true,
    ]);

    $response = $this->get(route('blog.show', $article->slug));

    $response->assertOk();
    $response->assertSee('<meta', false);
});

it('affiche le JSON-LD structure sans erreur de syntaxe', function () {
    $article = Article::factory()->create([
        'is_published' => true,
    ]);

    $response = $this->get(route('blog.show', $article->slug));

    $response->assertSee('application/ld+json', false);
});

it('retourne 404 pour un article inexistant', function () {
    $response = $this->get('/blog/slug-qui-nexiste-pas');

    $response->assertNotFound();
});

it('retourne 404 pour un article non publie', function () {
    $article = Article::factory()->create([
        'is_published' => false,
    ]);

    $response = $this->get(route('blog.show', $article->slug));

    $response->assertNotFound();
});
