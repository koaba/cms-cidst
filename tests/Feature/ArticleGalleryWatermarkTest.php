<?php

use App\Models\Article;
use App\Models\Media;
use App\Models\User;
use App\Services\WatermarkService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('public');
    Role::create(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
});

it('appelle le service de filigrane pour une image de galerie quand la case est cochee', function () {
    $article = Article::factory()->create();

    $this->mock(WatermarkService::class, function ($mock) {
        $mock->shouldReceive('watermarkImage')->once()->andReturn(true);
    });

    $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'images' => [UploadedFile::fake()->image('photo.jpg')],
        'apply_watermark_images' => '1',
    ]);
});

it('n\'appelle pas le service de filigrane sur la galerie quand la case n\'est pas cochee', function () {
    $article = Article::factory()->create();

    $this->mock(WatermarkService::class, function ($mock) {
        $mock->shouldReceive('watermarkImage')->never();
    });

    $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'images' => [UploadedFile::fake()->image('photo.jpg')],
    ]);
});

it('appelle le filigrane une fois par image quand plusieurs images sont envoyees', function () {
    $article = Article::factory()->create();

    $this->mock(WatermarkService::class, function ($mock) {
        $mock->shouldReceive('watermarkImage')->times(3)->andReturn(true);
    });

    $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'images' => [
            UploadedFile::fake()->image('un.jpg'),
            UploadedFile::fake()->image('deux.jpg'),
            UploadedFile::fake()->image('trois.jpg'),
        ],
        'apply_watermark_images' => '1',
    ]);
});

it('n\'applique jamais le filigrane a un media existant selectionne depuis la mediatheque', function () {
    $article = Article::factory()->create();
    $existing = Media::factory()->create(['mime_type' => 'image/jpeg']);

    $this->mock(WatermarkService::class, function ($mock) {
        $mock->shouldReceive('watermarkImage')->never();
    });

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'existing_media' => [$existing->id],
        'apply_watermark_images' => '1',
    ]);

    $response->assertSessionHasNoErrors();
    expect($article->fresh()->media()->where('media.id', $existing->id)->exists())->toBeTrue();
});

it('attache quand meme l\'image si le service de filigrane echoue silencieusement', function () {
    $article = Article::factory()->create();

    $this->mock(WatermarkService::class, function ($mock) {
        $mock->shouldReceive('watermarkImage')->once()->andReturn(false);
    });

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'images' => [UploadedFile::fake()->image('photo.jpg')],
        'apply_watermark_images' => '1',
    ]);

    $response->assertSessionHasNoErrors();
    expect($article->fresh()->media()->where('mime_type', 'like', 'image/%')->count())->toBe(1);
});