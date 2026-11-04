<?php

use App\Models\Article;
use App\Models\Page;
use App\Models\User;

beforeEach(function () {
    $this->injection = '</script><script>alert(1)</script>';

    $this->jsonLdOf = function (string $html): array {
        preg_match('#<script type="application/ld\+json">\s*(.*?)\s*</script>#s', $html, $matches);

        expect($matches)->toHaveKey(1);

        return json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
    };
});

it("n'autorise pas un titre de page a refermer le bloc JSON-LD", function () {
    $page = Page::factory()->create(['title' => $this->injection, 'slug' => 'page-injection']);

    $html = $this->get(route('pages.show', $page))->assertOk()->getContent();

    expect($html)->not->toContain('<script>alert(1)</script>')
        ->and(($this->jsonLdOf)($html)['name'])->toBe($this->injection);
});

it("n'autorise pas un titre d'article a refermer le bloc JSON-LD", function () {
    $article = Article::create([
        'title' => $this->injection,
        'slug' => 'article-injection',
        'content' => 'Contenu test',
        'user_id' => User::factory()->create()->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $html = $this->get(route('blog.show', $article))->assertOk()->getContent();

    expect($html)->not->toContain('<script>alert(1)</script>')
        ->and(($this->jsonLdOf)($html)['headline'])->toBe($this->injection);
});
