<?php

use App\Models\Article;
use App\Models\Page;
use App\Models\User;

function renderedJsonLd(string $html): array
{
    preg_match('#<script type="application/ld\+json">\s*(.*?)\s*</script>#s', $html, $matches);

    expect($matches)->toHaveKey(1);

    $data = json_decode($matches[1], true);

    expect($data)->toBeArray();

    return $data;
}

it('rend un JSON-LD valide avec @context pour une page', function () {
    $page = Page::factory()->create(['title' => 'Page JSON-LD', 'slug' => 'page-jsonld']);

    $response = $this->get(route('pages.show', $page));

    $response->assertOk();

    $data = renderedJsonLd($response->getContent());

    expect($data['@context'])->toBe('https://schema.org')
        ->and($data['@type'])->toBe('WebPage')
        ->and($data['name'])->toBe('Page JSON-LD');
    expect($response->getContent())->not->toContain('__contextArgs');
});

it('rend un JSON-LD valide avec @context pour un article', function () {
    $user = User::factory()->create();

    $article = Article::create([
        'title' => 'Article JSON-LD',
        'slug' => 'article-jsonld',
        'content' => 'Contenu test',
        'user_id' => $user->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get(route('blog.show', $article));

    $response->assertOk();

    $data = renderedJsonLd($response->getContent());

    expect($data['@context'])->toBe('https://schema.org')
        ->and($data['@type'])->toBe('NewsArticle')
        ->and($data['headline'])->toBe('Article JSON-LD');
    expect($response->getContent())->not->toContain('__contextArgs');
});

it('ne laisse aucun code PHP compile dans le JSON-LD', function () {
    $page = Page::factory()->create(['slug' => 'page-sans-php']);

    $html = $this->get(route('pages.show', $page))->getContent();

    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

    expect($matches[1])->not->toContain('<?php')
        ->and(array_keys(renderedJsonLd($html)))->toContain('@context');
});
