<?php

use App\Models\Article;
use App\Models\Media;
use App\Services\SeoService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Queue::fake());

function seoUrl(string $path): string
{
    return Storage::disk('public')->url($path);
}

function seoGalleryMedia(Article $article, string $path, string $mime, int $order): Media
{
    $media = Media::factory()->create(['path' => $path, 'mime_type' => $mime]);
    $article->media()->attach($media->id, ['order' => $order]);

    return $media;
}

it('utilise la couverture quand la colonne image est vide', function () {
    $article = Article::factory()->create();
    $cover = Media::factory()->create(['path' => 'articles/cover.jpg']);
    $article->coverMedia()->associate($cover)->save();

    expect(SeoService::image($article->fresh()))->toBe(seoUrl('articles/cover.jpg'));
});

it('prefere la couverture a la colonne image', function () {
    $article = Article::factory()->create(['image' => 'articles/ancienne.jpg']);
    $cover = Media::factory()->create(['path' => 'articles/cover.jpg']);
    $article->coverMedia()->associate($cover)->save();

    expect(SeoService::image($article->fresh()))->toBe(seoUrl('articles/cover.jpg'));
});

it('retombe sur la colonne image sans couverture', function () {
    $article = Article::factory()->create(['image' => 'articles/ancienne.jpg']);

    expect(SeoService::image($article->fresh()))->toBe(seoUrl('articles/ancienne.jpg'));
});

it('retombe sur la premiere image de la galerie', function () {
    $article = Article::factory()->create();
    seoGalleryMedia($article, 'articles/gallery/a.jpg', 'image/jpeg', 0);

    expect(SeoService::image($article->fresh()))->toBe(seoUrl('articles/gallery/a.jpg'));
});

it('ignore un PDF place avant une image dans la galerie', function () {
    $article = Article::factory()->create();
    seoGalleryMedia($article, 'articles/pdfs/doc.pdf', 'application/pdf', 0);
    seoGalleryMedia($article, 'articles/gallery/a.jpg', 'image/jpeg', 1);

    expect(SeoService::image($article->fresh()))->toBe(seoUrl('articles/gallery/a.jpg'));
});

it('ne renvoie pas l URL d un PDF quand il n y a aucune image', function () {
    $article = Article::factory()->create();
    seoGalleryMedia($article, 'articles/pdfs/doc.pdf', 'application/pdf', 0);

    expect(SeoService::image($article->fresh()))->toBeNull();
});

it('renvoie null sans aucun media', function () {
    $article = Article::factory()->create();

    expect(SeoService::image($article->fresh()))->toBeNull();
});
