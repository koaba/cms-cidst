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