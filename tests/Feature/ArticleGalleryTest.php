<?php

use App\Models\Article;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('public');
    Role::create(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
});

it('ajoute une image a la galerie d\'un article via upload direct', function () {
    $article = Article::factory()->create();

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'images' => [UploadedFile::fake()->image('photo.jpg')],
    ]);

    $response->assertRedirect(route('admin.articles.index'));
    expect($article->fresh()->media)->toHaveCount(1);
});

it('ajoute une image existante a la galerie via selection mediatheque', function () {
    $article = Article::factory()->create();
    $media = Media::factory()->create();

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'existing_media' => [$media->id],
    ]);

    $response->assertRedirect(route('admin.articles.index'));
    expect($article->fresh()->media)->toHaveCount(1);
});

it('retire une image de la galerie d\'un article', function () {
    $article = Article::factory()->create();
    $media = Media::factory()->create();
    $article->media()->attach($media->id, ['order' => 0]);

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'delete_images' => [$media->id],
    ]);

    $response->assertRedirect(route('admin.articles.index'));
    expect($article->fresh()->media)->toHaveCount(0);
});