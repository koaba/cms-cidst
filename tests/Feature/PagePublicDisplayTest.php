<?php

use App\Models\Media;
use App\Models\Page;
use App\Models\User;

it('affiche l\'image de la page via la médiathèque', function () {
    $user = User::factory()->create();

    $page = Page::create([
        'title' => 'Page avec image',
        'slug' => 'page-avec-image',
        'content' => 'Contenu de la page',
        'user_id' => $user->id,
        'is_published' => true,
    ]);

    $media = Media::create([
        'path' => 'pages/test.jpg',
        'original_name' => 'test.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
    ]);
    $page->media()->attach($media->id, ['order' => 0]);

    $response = $this->get("/pages/{$page->slug}");

    $response->assertOk();
    $response->assertSee('test.jpg');
});

it('n\'affiche aucune image quand la page n\'a pas de média', function () {
    $user = User::factory()->create();

    $page = Page::create([
        'title' => 'Page sans image',
        'slug' => 'page-sans-image',
        'content' => 'Contenu de la page',
        'user_id' => $user->id,
        'is_published' => true,
    ]);

    $response = $this->get("/pages/{$page->slug}");

    $response->assertOk();
    $response->assertDontSee('<img');
});

it('retourne 404 pour une page non publiée', function () {
    $user = User::factory()->create();

    $page = Page::create([
        'title' => 'Page non publiée',
        'slug' => 'page-non-publiee',
        'content' => 'Contenu',
        'user_id' => $user->id,
        'is_published' => false,
    ]);

    $this->get("/pages/{$page->slug}")->assertNotFound();
});

it('retourne 404 pour une page planifiée dans le futur', function () {
    $user = User::factory()->create();

    $page = Page::create([
        'title' => 'Page planifiée',
        'slug' => 'page-planifiee',
        'content' => 'Contenu',
        'user_id' => $user->id,
        'is_published' => true,
        'published_at' => now()->addDays(3),
    ]);

    $this->get("/pages/{$page->slug}")->assertNotFound();
});

it('affiche une page dont la date de publication est passée', function () {
    $user = User::factory()->create();

    $page = Page::create([
        'title' => 'Page publiée hier',
        'slug' => 'page-publiee-hier',
        'content' => 'Contenu',
        'user_id' => $user->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $this->get("/pages/{$page->slug}")->assertOk();
});

it('affiche une page publiée sans date de planification', function () {
    $user = User::factory()->create();

    $page = Page::create([
        'title' => 'Page publiée sans date',
        'slug' => 'page-publiee-sans-date',
        'content' => 'Contenu',
        'user_id' => $user->id,
        'is_published' => true,
        'published_at' => null,
    ]);

    $this->get("/pages/{$page->slug}")->assertOk();
});

it('exclut de la liste publique les pages planifiées dans le futur', function () {
    $user = User::factory()->create();

    $pageVisible = Page::create([
        'title' => 'Page visible maintenant',
        'slug' => 'page-visible-maintenant',
        'content' => 'Contenu',
        'user_id' => $user->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $pageFuture = Page::create([
        'title' => 'Page future invisible',
        'slug' => 'page-future-invisible',
        'content' => 'Contenu',
        'user_id' => $user->id,
        'is_published' => true,
        'published_at' => now()->addDays(3),
    ]);

    $response = $this->get(route('pages.index'));

    $response->assertOk();
    $response->assertSee($pageVisible->title);
    $response->assertDontSee($pageFuture->title);
});