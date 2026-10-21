<?php

use App\Models\Article;
use App\Models\Media;
use App\Models\Slider;
use App\Services\MediaOverviewService;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

function inUseOrNull(Media $media): ?bool
{
    try {
        return app(MediaOverviewService::class)->isInUse($media);
    } catch (Throwable $e) {
        return null;
    }
}

function inUseMedia(): Media
{
    return Media::create([
        'path' => 'tests/'.uniqid().'.jpg',
        'original_name' => 'fichier.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
        'type' => 'image',
    ]);
}

it('considere un media sans aucun usage comme non utilise', function () {
    expect(inUseOrNull(inUseMedia()))->toBe(false);
});

it('considere utilise un media rattache par le pivot mediables', function () {
    $media = inUseMedia();
    $slider = Slider::create(['title' => 'S', 'image' => $media->path, 'order' => 0, 'is_active' => true]);
    $slider->media()->attach($media->id, ['order' => 0]);

    expect(inUseOrNull($media))->toBe(true);
});

it('considere utilise un media couverture d un article', function () {
    $media = inUseMedia();
    $article = Article::factory()->create();
    $article->coverMedia()->associate($media)->save();

    expect(inUseOrNull($media))->toBe(true);
});

it('considere utilise un media dont le chemin est dans articles.image', function () {
    $media = inUseMedia();
    Article::factory()->create(['image' => $media->path]);

    expect(inUseOrNull($media))->toBe(true);
});

it('considere utilise un media dont le chemin est dans seo_metas.og_image', function () {
    $media = inUseMedia();
    $article = Article::factory()->create();
    $article->seo()->create(['og_image' => $media->path]);

    expect(inUseOrNull($media))->toBe(true);
});
