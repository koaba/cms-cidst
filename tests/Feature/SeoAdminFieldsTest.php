<?php

use App\Http\Requests\Admin\StoreArticleRequest;
use App\Http\Requests\Admin\StorePageRequest;
use App\Models\Page;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->actingAs(adminUser());

    $this->pageData = ['title' => 'Titre', 'content' => 'Contenu', 'published_at' => '2026-10-22T14:30'];

    $this->seoErrors = fn (array $seo) => Validator::make(
        ['seo' => $seo],
        collect((new StorePageRequest)->rules())->filter(fn ($rule, $key) => str_starts_with($key, 'seo'))->all(),
    )->errors()->keys();
});

it('accepte une URL canonique http ou https', function () {
    expect(($this->seoErrors)(['canonical_url' => 'https://exemple.org/a?b=1']))->toBe([])
        ->and(($this->seoErrors)(['canonical_url' => 'http://exemple.org/a']))->toBe([]);
});

it('refuse une URL canonique qui n est pas http ou https', function (string $value) {
    expect(($this->seoErrors)(['canonical_url' => $value]))->toBe(['seo.canonical_url']);
})->with(['javascript:alert(1)', 'ftp://exemple.org/a', 'pas une url', 'https://exemple.org/'.str_repeat('a', 250)]);

it('limite no_index a une valeur booleenne', function () {
    expect(($this->seoErrors)(['no_index' => '1']))->toBe([])
        ->and(($this->seoErrors)(['no_index' => '0']))->toBe([])
        ->and(($this->seoErrors)(['no_index' => 'oui']))->toBe(['seo.no_index']);
});

it('applique les memes regles canonical et no_index aux pages et aux articles', function () {
    $seoRules = fn (array $rules) => collect($rules)->filter(fn ($rule, $key) => str_starts_with($key, 'seo'))->all();

    $pages = $seoRules((new StorePageRequest)->rules());
    $articles = $seoRules((new StoreArticleRequest)->rules());

    expect($pages)->toHaveKeys(['seo.canonical_url', 'seo.no_index'])
        ->and($articles)->toEqual($pages);
});

it('enregistre no_index et canonical_url a la creation d une page', function () {
    $this->post(route('admin.pages.store'), $this->pageData + [
        'seo' => ['canonical_url' => 'https://exemple.org/page', 'no_index' => '1'],
    ])->assertRedirect(route('admin.pages.index'));

    $seo = Page::firstOrFail()->seo;

    expect($seo?->canonical_url)->toBe('https://exemple.org/page')
        ->and($seo?->no_index)->toBeTrue();
});

it('remet no_index a faux et efface le canonical a l edition', function () {
    $page = Page::factory()->create();
    $page->seo()->create(['no_index' => true, 'canonical_url' => 'https://exemple.org/ancienne']);

    $this->put(route('admin.pages.update', $page), $this->pageData + [
        'seo' => ['canonical_url' => '', 'no_index' => '0'],
    ])->assertRedirect();

    $seo = $page->fresh()->seo;

    expect($seo->no_index)->toBeFalse()
        ->and($seo->canonical_url)->toBeNull();
});

it('affiche les champs canonical et no_index avec leurs valeurs a l edition', function () {
    $page = Page::factory()->create();
    $page->seo()->create(['no_index' => true, 'canonical_url' => 'https://exemple.org/page']);

    $html = $this->get(route('admin.pages.edit', $page))
        ->assertOk()
        ->assertSee('name="seo[canonical_url]"', false)
        ->assertSee('value="https://exemple.org/page"', false)
        ->assertSee('<input type="hidden" name="seo[no_index]" value="0">', false)
        ->getContent();

    expect($html)->toMatch('/name="seo\[no_index\]" value="1" class="checkbox"\s+checked/');
});

it('propose les champs SEO a la creation d un article', function () {
    $this->get(route('admin.articles.create'))
        ->assertOk()
        ->assertSee('name="seo[canonical_url]"', false)
        ->assertSee('name="seo[no_index]"', false);
});
