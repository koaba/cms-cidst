<?php

use App\Models\NewsTicker;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\SiteSetting;
use App\Support\PublicLayout;
use App\Support\PublicPageCache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

// Le magasin "array" des tests ne serialise pas : on l'y oblige, comme le cache "database" en local et en production.
beforeEach(function () {
    Queue::fake();
    config(['cache.stores.array.serialize' => true]);
    Cache::forgetDriver();
    Cache::flush();
});

it('relit les reglages du site depuis un cache qui serialise', function () {
    SiteSetting::current();
    app()->forgetInstance('site-settings.current');

    expect(SiteSetting::current())->toBeInstanceOf(SiteSetting::class);
});

it('relit le layout public depuis un cache qui serialise', function () {
    NewsTicker::factory()->create(['is_active' => true]);

    (new PublicLayout)->tickers();
    $tickers = (new PublicLayout)->tickers();

    expect($tickers)->toBeInstanceOf(Collection::class)
        ->and($tickers->first())->toBeInstanceOf(NewsTicker::class);
});

it('relit l arbre d une page depuis un cache qui serialise', function () {
    $page = Page::factory()->create(['is_published' => true]);
    $page->blocks()->create(['type' => 'texte', 'data' => ['content' => 'Texte'], 'order' => 0]);

    PublicPageCache::tree($page);
    $tree = PublicPageCache::tree($page);

    expect($tree['blocks'])->toBeInstanceOf(Collection::class)
        ->and($tree['blocks']->first())->toBeInstanceOf(PageBlock::class);
});

it('sert accueil, page publique et administration deux fois de suite', function () {
    $page = Page::factory()->create(['is_published' => true]);
    $this->actingAs(adminUser());

    foreach ([1, 2] as $_) {
        app()->forgetScopedInstances();
        app()->forgetInstance('site-settings.current');

        $this->get(route('home'))->assertOk();
        $this->get(route('pages.show', $page))->assertOk();
        $this->get('/admin')->assertOk();
    }
});
