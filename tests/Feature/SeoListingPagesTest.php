<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\PdfCategory;

it('titre et decrit chaque page de liste', function (string $url, string $title) {
    $title = str_replace(':site', config('app.name'), $title);

    $html = $this->get($url)->assertOk()->getContent();

    expect($html)->toContain('<title>'.e($title).'</title>')
        ->and($html)->toContain('<meta name="description" content="')
        ->and($html)->toContain('<meta property="og:description" content="');
})->with([
    'accueil' => ['/', "CIDST - Centre d'Information et de Documentation Scientifique et Technique"],
    'blog' => ['/blog', 'Blog — :site'],
    'pages' => ['/pages', 'Pages — :site'],
    'documents' => ['/documents', 'Documents — :site'],
    'sliders' => ['/sliders', 'À la une — :site'],
    'contact' => ['/contact', 'Contact — :site'],
    'recherche' => ['/recherche', 'Recherche — :site'],
]);

it('distingue le titre d une categorie de blog de celui du blog', function () {
    $category = Category::factory()->create(['name' => 'Physique quantique']);
    Article::factory()->create(['published_at' => now()->subDay()])->categories()->attach($category);

    $this->get(route('blog.category', $category))
        ->assertOk()
        ->assertSee('<title>Articles : Physique quantique — '.config('app.name').'</title>', false)
        ->assertSee('Physique quantique', false);
});

it('distingue le titre d une categorie de documents de celui des documents', function () {
    $category = PdfCategory::factory()->create(['name' => 'Rapports annuels']);

    $this->get(route('documents.category', $category))
        ->assertOk()
        ->assertSee('<title>Documents : Rapports annuels — '.config('app.name').'</title>', false);
});

it('exclut la recherche de l index sans toucher aux autres listes', function () {
    $this->get('/recherche')->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    $this->get('/blog')->assertOk()->assertDontSee('name="robots"', false);
});

it('conserve le numero de page dans le canonical des listes', function () {
    Article::factory()->count(10)->create(['published_at' => now()->subDay()]);

    $this->get('/blog?page=2')->assertOk()
        ->assertSee('<link rel="canonical" href="'.url('/blog').'?page=2">', false);
    $this->get('/blog')->assertOk()
        ->assertSee('<link rel="canonical" href="'.url('/blog').'">', false);
    $this->get('/blog?page=1')->assertOk()
        ->assertSee('<link rel="canonical" href="'.url('/blog').'">', false);
});

it('definit un titre et une description de 160 caracteres maximum par page', function () {
    $pages = config('seo.pages');

    expect($pages)->toBeArray()
        ->and(array_keys($pages))->toEqualCanonicalizing([
            'home', 'blog', 'blog_category', 'pages', 'documents', 'documents_category', 'sliders', 'contact', 'search',
        ])
        ->and(collect($pages)->every(fn (array $meta) => filled($meta['title'] ?? null)
            && filled($meta['description'] ?? null)
            && mb_strlen($meta['description']) <= 160))->toBeTrue();
});
