<?php

use App\Models\Article;
use App\Models\Media;
use App\Models\User;
use Spatie\Permission\Models\Role;

it('lists all uploaded media for a Super Admin', function () {
    Role::create(['name' => 'Super Admin']);
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    Media::create([
        'path' => 'test/image-un.jpg',
        'original_name' => 'image-un.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 102400,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.media.index'));

    $response->assertOk();
    $response->assertSee('image-un.jpg');
});

it('filters media by filename search', function () {
    Role::create(['name' => 'Super Admin']);
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    Media::create([
        'path' => 'test/logo-cidst.png',
        'original_name' => 'logo-cidst.png',
        'mime_type' => 'image/png',
        'size' => 51200,
    ]);
    Media::create([
        'path' => 'test/photo-evenement.jpg',
        'original_name' => 'photo-evenement.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 204800,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.media.index', ['q' => 'logo']));

    $response->assertOk();
    $response->assertSee('logo-cidst.png');
    $response->assertDontSee('photo-evenement.jpg');
});

it('shows which article uses a given media file', function () {
    Role::create(['name' => 'Super Admin']);
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $article = Article::factory()->create(['title' => 'Article de test médiathèque']);
    $media = Media::create([
        'path' => 'test/image-liee.jpg',
        'original_name' => 'image-liee.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 102400,
    ]);
    $article->media()->attach($media->id, ['order' => 0]);

    $response = $this->actingAs($admin)->get(route('admin.media.index'));

    $response->assertOk();
    $response->assertSee('Article de test médiathèque');
});

it('denies media library access to a user without the Super Admin role', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.media.index'));

    $response->assertForbidden();
});