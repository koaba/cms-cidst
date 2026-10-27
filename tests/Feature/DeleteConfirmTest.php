<?php

use App\Models\Article;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Hash;

function setDeleteCode(string $code): void
{
    SiteSetting::current()->forceFill(['delete_code_hash' => Hash::make($code)])->save();
}

it('refuse la suppression sans code a Publication', function () {
    setDeleteCode('4242');
    $article = Article::factory()->create();
    $this->actingAs(publicationUser())->delete(route('admin.articles.destroy', $article))
        ->assertRedirect()
        ->assertSessionHasErrors('delete_code');
    expect(Article::find($article->id))->not->toBeNull();
});

it('refuse la suppression avec un mauvais code a Publication', function () {
    setDeleteCode('4242');
    $article = Article::factory()->create();
    $this->actingAs(publicationUser())->delete(route('admin.articles.destroy', $article), ['delete_code' => '0000'])
        ->assertRedirect()
        ->assertSessionHasErrors('delete_code');
    expect(Article::find($article->id))->not->toBeNull();
});

it('supprime avec le bon code pour Publication', function () {
    setDeleteCode('4242');
    $article = Article::factory()->create();
    $this->actingAs(publicationUser())->delete(route('admin.articles.destroy', $article), ['delete_code' => '4242'])
        ->assertRedirect()->assertSessionHasNoErrors();
    expect(Article::find($article->id))->toBeNull();
});

it('refuse Publication tant qu aucun code nest defini', function () {
    $article = Article::factory()->create();
    $this->actingAs(publicationUser())->delete(route('admin.articles.destroy', $article), ['delete_code' => '4242'])
        ->assertRedirect()
        ->assertSessionHasErrors('delete_code');
    expect(Article::find($article->id))->not->toBeNull();
});

it('exempte le Super Admin du code', function () {
    setDeleteCode('4242');
    $article = Article::factory()->create();
    $this->actingAs(adminUser())->delete(route('admin.articles.destroy', $article))
        ->assertRedirect();
    expect(Article::find($article->id))->toBeNull();
});
