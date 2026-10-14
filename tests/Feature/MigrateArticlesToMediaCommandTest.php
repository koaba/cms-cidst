<?php

use App\Models\Article;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    Storage::fake('public');
});

function articleWithRawImage(string $name = 'a.jpg'): Article
{
    UploadedFile::fake()->image($name, 800, 400)->storeAs('articles', $name, 'public');

    return Article::factory()->create(['image' => 'articles/'.$name]);
}

it('cree un media de couverture avec dimensions pour chaque article', function () {
    $article = articleWithRawImage();

    $this->artisan('articles:migrate-to-media')->assertExitCode(0);

    $media = $article->fresh()->coverMedia;

    expect($media)->not->toBeNull()
        ->and($media->path)->toBe('articles/a.jpg')
        ->and($media->type)->toBe('image')
        ->and($media->width)->toBe(800)
        ->and($media->height)->toBe(400)
        ->and($article->fresh()->image)->toBe('articles/a.jpg');
});

it('est idempotente', function () {
    articleWithRawImage();

    $this->artisan('articles:migrate-to-media')->assertExitCode(0);
    $this->artisan('articles:migrate-to-media')->assertExitCode(0);

    expect(Media::count())->toBe(1);
});

it('n ecrit rien en dry-run', function () {
    $article = articleWithRawImage();

    $this->artisan('articles:migrate-to-media', ['--dry-run' => true])->assertExitCode(0);

    expect(Media::count())->toBe(0)
        ->and($article->fresh()->cover_media_id)->toBeNull();
});

it('ignore un article dont le fichier est introuvable', function () {
    $article = Article::factory()->create(['image' => 'articles/absent.jpg']);

    $this->artisan('articles:migrate-to-media')->assertExitCode(0);

    expect(Media::count())->toBe(0)
        ->and($article->fresh()->cover_media_id)->toBeNull();
});
