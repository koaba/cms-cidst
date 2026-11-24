<?php

use App\Models\Article;
use App\Models\PageView;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

const TP_IP = '203.0.113.9';

function tpGet(Article $article, string $ip = TP_IP)
{
    return test()->withServerVariables(['REMOTE_ADDR' => $ip])
        ->get(route('blog.show', $article));
}

it('enregistre une vue anonyme sur un article publie', function () {
    $article = Article::factory()->create();

    tpGet($article)->assertOk();

    $view = PageView::query()->sole();

    expect($view->viewable_type)->toBe(Article::class)
        ->and($view->viewable_id)->toBe($article->id)
        ->and($view->ip_hash)->toBe(hash('sha256', TP_IP.config('app.key')))
        ->and($view->ip_hash)->not->toContain(TP_IP);
});

it('ne compte qu une vue par ip et par contenu dans l heure', function () {
    $article = Article::factory()->create();

    tpGet($article);
    tpGet($article);

    expect(PageView::count())->toBe(1);
});

it('recompte la meme ip apres la fenetre d une heure', function () {
    $this->travelTo(now()->setTime(12, 0));
    $article = Article::factory()->create();

    tpGet($article);
    $this->travel(61)->minutes();
    tpGet($article);

    expect(PageView::count())->toBe(2);
});

it('compte separement deux ip differentes', function () {
    $article = Article::factory()->create();

    tpGet($article, '203.0.113.1');
    tpGet($article, '203.0.113.2');

    expect(PageView::count())->toBe(2);
});

it('compte separement deux articles pour la meme ip', function () {
    tpGet(Article::factory()->create());
    tpGet(Article::factory()->create());

    expect(PageView::count())->toBe(2);
});

it('ne compte pas une page en 404', function () {
    $draft = Article::factory()->create(['is_published' => false]);

    tpGet($draft)->assertNotFound();

    expect(PageView::count())->toBe(0);
});

it('tronque le user agent a 255 caracteres', function () {
    $article = Article::factory()->create();

    $this->withHeaders(['User-Agent' => str_repeat('a', 400)])
        ->withServerVariables(['REMOTE_ADDR' => TP_IP])
        ->get(route('blog.show', $article))
        ->assertOk();

    expect(strlen((string) PageView::query()->value('user_agent')))->toBe(255);
});
