<?php

use App\Models\Article;

beforeEach(function () {
    $this->tryCode = function ($user, Article $article, string $code) {
        return $this->actingAs($user)->delete(route('admin.articles.destroy', $article), ['delete_code' => $code]);
    };
});

it('refuse le bon code apres 5 mauvais essais', function () {
    setDeleteCode('4242');
    $user = publicationUser();
    $article = Article::factory()->create();

    foreach (range(1, 5) as $i) {
        ($this->tryCode)($user, $article, '000'.$i)->assertSessionHasErrors('delete_code');
    }

    ($this->tryCode)($user, $article, '4242')->assertSessionHasErrors('delete_code');
    expect(Article::find($article->id))->not->toBeNull();
});

it('autorise la suppression apres moins de 5 mauvais essais', function () {
    setDeleteCode('4242');
    $user = publicationUser();
    $article = Article::factory()->create();

    foreach (range(1, 4) as $i) {
        ($this->tryCode)($user, $article, '000'.$i)->assertSessionHasErrors('delete_code');
    }

    ($this->tryCode)($user, $article, '4242')->assertSessionHasNoErrors();
    expect(Article::find($article->id))->toBeNull();
});

it('remet le compteur a zero apres un code correct', function () {
    setDeleteCode('4242');
    $user = publicationUser();

    foreach (range(1, 4) as $i) {
        ($this->tryCode)($user, Article::factory()->create(), '000'.$i);
    }
    ($this->tryCode)($user, Article::factory()->create(), '4242');

    $article = Article::factory()->create();
    foreach (range(1, 4) as $i) {
        ($this->tryCode)($user, $article, '000'.$i);
    }
    ($this->tryCode)($user, $article, '4242')->assertSessionHasNoErrors();
    expect(Article::find($article->id))->toBeNull();
});

it('limite chaque utilisateur separement', function () {
    setDeleteCode('4242');
    $blocked = publicationUser();
    $other = publicationUser();
    $article = Article::factory()->create();

    foreach (range(1, 5) as $i) {
        ($this->tryCode)($blocked, $article, '000'.$i);
    }

    ($this->tryCode)($other, $article, '4242')->assertSessionHasNoErrors();
    expect(Article::find($article->id))->toBeNull();
});

it('ne limite jamais le Super Admin', function () {
    setDeleteCode('4242');
    $admin = adminUser();

    foreach (range(1, 8) as $i) {
        $article = Article::factory()->create();
        ($this->tryCode)($admin, $article, '000'.$i)->assertSessionHasNoErrors();
        expect(Article::find($article->id))->toBeNull();
    }
});
