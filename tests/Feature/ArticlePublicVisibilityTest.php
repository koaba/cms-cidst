<?php

use App\Models\Article;
use App\Models\User;

it('affiche un article publié avec une date de publication passée', function () {
    $user = User::factory()->create();

    $article = Article::create([
        'title' => 'Article visible',
        'slug' => 'article-visible',
        'content' => 'Contenu test',
        'user_id' => $user->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get('/blog');

    $response->assertOk();
    $response->assertSee($article->title);
});

it('masque un article dont la date de publication est dans le futur', function () {
    $user = User::factory()->create();

    $article = Article::create([
        'title' => 'Article programmé futur',
        'slug' => 'article-programme-futur',
        'content' => 'Contenu test',
        'user_id' => $user->id,
        'is_published' => true,
        'published_at' => now()->addDay(),
    ]);

    $response = $this->get('/blog');

    $response->assertOk();
    $response->assertDontSee($article->title);
});

it('masque un article avec published_at null', function () {
    $user = User::factory()->create();

    $article = Article::create([
        'title' => 'Article sans date',
        'slug' => 'article-sans-date',
        'content' => 'Contenu test',
        'user_id' => $user->id,
        'is_published' => true,
        'published_at' => null,
    ]);

    $response = $this->get('/blog');

    $response->assertOk();
    $response->assertDontSee($article->title);
});

it('masque un article non publié même avec published_at passé', function () {
    $user = User::factory()->create();

    $article = Article::create([
        'title' => 'Article brouillon',
        'slug' => 'article-brouillon',
        'content' => 'Contenu test',
        'user_id' => $user->id,
        'is_published' => false,
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get('/blog');

    $response->assertOk();
    $response->assertDontSee($article->title);
});