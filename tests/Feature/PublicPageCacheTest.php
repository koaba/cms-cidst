<?php

use App\Models\Media;
use App\Models\Page;
use App\Services\PageBuilder\BlockSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    Cache::flush();
});

function newRequest(Page $page): TestResponse
{
    app()->forgetScopedInstances();
    app()->forgetInstance('site-settings.current');

    return test()->get(route('pages.show', $page));
}

function pageWithTextBlock(string $content = 'Texte initial'): array
{
    $page = Page::factory()->create(['is_published' => true]);
    $block = $page->blocks()->create(['type' => 'texte', 'data' => ['content' => $content], 'order' => 0]);

    return [$page, $block];
}

it('ne requete ni blocs, ni medias, ni seo une fois la page en cache', function () {
    [$page] = pageWithTextBlock();
    newRequest($page)->assertOk();

    $tables = [];
    DB::listen(function ($query) use (&$tables) {
        if (preg_match('/from [`"]?(page_blocks|media|seo_metas|pdf_documents)[`"]?/i', $query->sql, $m)) {
            $tables[] = $m[1];
        }
    });

    newRequest($page)->assertOk()->assertSee('Texte initial');

    expect($tables)->toBe([]);
});

it('reflete la modification d un bloc', function () {
    [$page, $block] = pageWithTextBlock();
    newRequest($page)->assertSee('Texte initial');

    $block->update(['data' => ['content' => 'Texte modifie']]);

    newRequest($page)->assertSee('Texte modifie')->assertDontSee('Texte initial');
});

it('reflete la suppression d un bloc', function () {
    [$page, $block] = pageWithTextBlock();
    newRequest($page)->assertSee('Texte initial');

    $block->delete();

    newRequest($page)->assertDontSee('Texte initial');
});

it('reflete une sauvegarde du page builder', function () {
    [$page, $block] = pageWithTextBlock();
    newRequest($page)->assertSee('Texte initial');

    app(BlockSyncService::class)->sync($page, [
        ['id' => $block->id, 'type' => 'texte', 'data' => ['content' => 'Texte du builder']],
    ]);

    newRequest($page)->assertSee('Texte du builder')->assertDontSee('Texte initial');
});

it('reflete le changement d un media de bloc', function () {
    $page = Page::factory()->create(['is_published' => true]);
    $block = $page->blocks()->create(['type' => 'image', 'data' => ['alt' => 'Une image'], 'order' => 0]);
    $media = Media::factory()->create(['path' => 'blocks/ancien.jpg', 'type' => 'image']);
    $block->media()->attach($media->id, ['order' => 1]);
    newRequest($page)->assertSee('ancien.jpg');

    $media->update(['path' => 'blocks/nouveau.jpg']);

    newRequest($page)->assertSee('nouveau.jpg')->assertDontSee('ancien.jpg');
});

it('reflete le changement du seo de la page', function () {
    [$page] = pageWithTextBlock();
    newRequest($page)->assertDontSee('Meta titre personnalise');

    $page->seo()->create(['meta_title' => 'Meta titre personnalise']);

    newRequest($page)->assertSee('Meta titre personnalise');
});

it('une page depubliee repond 404 meme si son arbre est en cache', function () {
    [$page] = pageWithTextBlock();
    newRequest($page)->assertOk();

    $page->update(['is_published' => false]);

    newRequest($page)->assertNotFound();
});

it('expire l arbre en cache apres le ttl meme sans evenement', function () {
    [$page, $block] = pageWithTextBlock();
    newRequest($page)->assertSee('Texte initial');

    DB::table('page_blocks')->where('id', $block->id)->update(['data' => json_encode(['content' => 'Modif directe en base'])]);
    newRequest($page)->assertSee('Texte initial');

    $this->travel(config('display.page_cache_ttl') + 1)->seconds();

    newRequest($page)->assertSee('Modif directe en base');
});
