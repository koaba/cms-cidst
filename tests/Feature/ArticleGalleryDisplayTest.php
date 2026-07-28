<?php

use App\Models\Article;
use App\Models\Media;
use App\Models\User;

it('affiche la galerie en mode diaporama quand gallery_display est slideshow', function () {
    $user = User::factory()->create();

    $article = Article::create([
        'title' => 'Article diaporama',
        'slug' => 'article-diaporama',
        'content' => 'Contenu',
        'user_id' => $user->id,
        'is_published' => true,
        'published_at' => now(),
        'gallery_display' => 'slideshow',
    ]);

    $media = Media::create([
        'path' => 'articles/gallery/test.jpg',
        'original_name' => 'test.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
    ]);
    $article->media()->attach($media->id, ['order' => 0]);

    $response = $this->get("/blog/{$article->slug}");

    $response->assertOk();
    $response->assertSee('snap-x', false);
});

it('affiche la galerie en mode grille quand gallery_display est grid', function () {
    $user = User::factory()->create();

    $article = Article::create([
        'title' => 'Article grille',
        'slug' => 'article-grille',
        'content' => 'Contenu',
        'user_id' => $user->id,
        'is_published' => true,
        'published_at' => now(),
        'gallery_display' => 'grid',
    ]);

    $media = Media::create([
        'path' => 'articles/gallery/test2.jpg',
        'original_name' => 'test2.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
    ]);
    $article->media()->attach($media->id, ['order' => 0]);

    $response = $this->get("/blog/{$article->slug}");

    $response->assertOk();
    $response->assertDontSee('snap-x', false);
});

it('n\'affiche aucune galerie quand l\'article n\'a pas de médias', function () {
    $user = User::factory()->create();

    $article = Article::create([
        'title' => 'Article sans galerie',
        'slug' => 'article-sans-galerie',
        'content' => 'Contenu',
        'user_id' => $user->id,
        'is_published' => true,
        'published_at' => now(),
        'gallery_display' => 'slideshow',
    ]);

    $response = $this->get("/blog/{$article->slug}");

    $response->assertOk();
    $response->assertDontSee('Galerie');
});