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

function articleWithCover(bool $withMedia = true): Article
{
    $article = Article::factory()->create([
        'is_published' => true,
        'published_at' => now()->subDay(),
        'image' => 'articles/raw.jpg',
    ]);

    if ($withMedia) {
        $media = Media::factory()->create([
            'path' => 'articles/cover.jpg',
            'type' => 'image',
            'width' => 2000,
            'height' => 1000,
            'variants' => [480 => 'articles/variants/cover-480.webp'],
        ]);
        $article->coverMedia()->associate($media)->save();
    }

    return $article;
}

function imgTag(string $html, string $marker): string
{
    preg_match_all('/<img\b[^>]*>/s', $html, $matches);

    return collect($matches[0])->first(fn ($t) => str_contains($t, $marker), '');
}

it('affiche la couverture de l article via srcset et priorite', function () {
    $article = articleWithCover();

    $tag = imgTag($this->get(route('blog.show', $article))->assertOk()->getContent(), 'max-h-[500px]');

    expect($tag)
        ->toContain('srcset="'.Storage::disk('public')->url('articles/variants/cover-480.webp').' 480w')
        ->toContain('width="2000"')
        ->toContain('fetchpriority="high"')
        ->toContain('alt="'.$article->title.'"');
});

it('affiche la couverture dans la liste via srcset en lazy', function () {
    articleWithCover();

    $tag = imgTag($this->get(route('blog.index'))->assertOk()->getContent(), 'w-full aspect-video object-cover');

    expect($tag)
        ->toContain('srcset="'.Storage::disk('public')->url('articles/variants/cover-480.webp').' 480w')
        ->toContain('loading="lazy"');
});

it('repli sur l image brute sans media de couverture', function () {
    $article = articleWithCover(false);

    $tag = imgTag($this->get(route('blog.show', $article))->assertOk()->getContent(), 'max-h-[500px]');

    expect($tag)
        ->toContain('src="'.Storage::url('articles/raw.jpg').'"')
        ->not->toContain('srcset');
});
