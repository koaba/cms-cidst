<?php

use App\Models\Article;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

function articleWithDiaporama(): array
{
    $article = Article::factory()->create(['is_published' => true, 'published_at' => now()->subDay()]);
    $diaporama = $article->diaporamas()->create(['title' => 'Visite', 'order' => 0]);

    foreach ([0, 1] as $i) {
        $diaporama->media()->attach(
            Media::factory()->create([
                'path' => 'diaporamas/slide-'.$i.'.jpg',
                'type' => 'image',
                'width' => 2000,
                'height' => 1000,
                'variants' => [480 => 'diaporamas/variants/slide-'.$i.'-480.webp'],
            ]),
            ['order' => $i]
        );
    }

    return [$article, $diaporama];
}

it('affiche le premier slide du diaporama via srcset', function () {
    [$article] = articleWithDiaporama();

    $html = $this->get(route('blog.show', $article))->assertOk()->getContent();

    preg_match_all('/<img\b[^>]*>/s', $html, $matches);
    $tag = collect($matches[0])->first(fn ($t) => str_contains($t, 'diaporama-auto-img'), '');

    expect($tag)
        ->toContain('srcset="'.Storage::disk('public')->url('diaporamas/variants/slide-0-480.webp').' 480w')
        ->toContain('width="2000"')
        ->toContain('alt="Visite"')
        ->toContain('sizes="(min-width: 672px) 672px, 100vw"');
});

it('fournit le srcset de chaque slide au carrousel JavaScript', function () {
    [$article, $diaporama] = articleWithDiaporama();

    $html = $this->get(route('blog.show', $article))->assertOk()->getContent();

    preg_match('/<script type="application\/json" id="diaporama-data-'.$diaporama->id.'">(.*?)<\/script>/s', $html, $m);
    $items = json_decode($m[1] ?? '[]', true);

    expect($items)->toHaveCount(2)
        ->and($items[0]['srcset'])->toContain('slide-0-480.webp 480w')
        ->and($items[1]['srcset'])->toContain('slide-1-480.webp 480w');
});
