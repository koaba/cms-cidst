<?php

use App\Models\Article;
use App\Models\Page;
use App\Models\PdfDocument;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::forget('sitemap.xml');
});

it('liste le blog, les pages et les documents quand ils ont du contenu', function () {
    Article::factory()->create(['is_published' => true]);
    Page::factory()->create(['is_published' => true]);
    PdfDocument::factory()->create();

    $this->get(route('sitemap'))->assertOk()
        ->assertSee('<loc>'.route('blog.index').'</loc>', false)
        ->assertSee('<loc>'.route('pages.index').'</loc>', false)
        ->assertSee('<loc>'.route('documents.index').'</loc>', false);
});

it('omet les listes sans contenu', function () {
    $this->get(route('sitemap'))->assertOk()
        ->assertDontSee('<loc>'.route('blog.index').'</loc>', false)
        ->assertDontSee('<loc>'.route('pages.index').'</loc>', false)
        ->assertDontSee('<loc>'.route('documents.index').'</loc>', false);
});

it('garde la liste du blog quand tous ses articles sont no_index', function () {
    $article = Article::factory()->create(['is_published' => true]);
    $article->seo()->create(['no_index' => true]);

    $this->get(route('sitemap'))->assertOk()
        ->assertSee('<loc>'.route('blog.index').'</loc>', false)
        ->assertDontSee('<loc>'.$article->publicUrl().'</loc>', false);
});

it('date une liste avec la derniere modification de son contenu', function () {
    Article::factory()->create(['is_published' => true, 'updated_at' => now()->subDays(3)]);
    $recent = Article::factory()->create(['is_published' => true, 'updated_at' => now()->subDay()]);

    $expected = $recent->fresh()->updated_at->toAtomString();

    expect($this->get(route('sitemap'))->getContent())->toMatch(
        '#<loc>'.preg_quote(route('blog.index'), '#').'</loc>\s*<lastmod>'.preg_quote($expected, '#').'</lastmod>#'
    );
});

it('inclut chaque document PDF dans le sitemap', function () {
    $document = PdfDocument::factory()->create(['title' => 'Rapport annuel']);

    $this->get(route('sitemap'))->assertOk()
        ->assertSee('<loc>'.$document->publicUrl().'</loc>', false);
});

it('vide le cache du sitemap quand un document PDF change', function () {
    $this->get(route('sitemap'))->assertOk();

    $document = PdfDocument::factory()->create();
    $this->get(route('sitemap'))->assertSee('<loc>'.$document->publicUrl().'</loc>', false);

    $document->delete();
    $this->get(route('sitemap'))->assertDontSee('<loc>'.$document->publicUrl().'</loc>', false);
});
