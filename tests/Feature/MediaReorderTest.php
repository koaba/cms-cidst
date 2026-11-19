<?php

use App\Models\Article;
use App\Models\Diaporama;
use App\Models\Media;

function mroMedia(Article|Diaporama $owner, int $order): Media
{
    $media = Media::factory()->create();
    $owner->media()->attach($media->id, ['order' => $order]);

    return $media;
}

/** @return array<int, int> valeur du pivot order par id de media, dans l'ordre de lecture */
function mroOrders(Article|Diaporama $owner): array
{
    return $owner->media()->get()
        ->mapWithKeys(fn (Media $media) => [$media->id => (int) $media->pivot->order])
        ->all();
}

function mroPost(string $type, int $id, array $ids)
{
    return test()->actingAs(adminUser())->postJson(route('admin.media.reorder'), [
        'mediable_type' => $type,
        'mediable_id' => $id,
        'ordered_ids' => $ids,
    ]);
}

it('enregistre le nouvel ordre des medias d un article', function () {
    $article = Article::factory()->create();
    $a = mroMedia($article, 0);
    $b = mroMedia($article, 1);
    $c = mroMedia($article, 2);

    mroPost('article', $article->id, [$c->id, $a->id, $b->id])
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    expect(mroOrders($article->fresh()))->toBe([$c->id => 0, $a->id => 1, $b->id => 2]);
});

it('ignore les medias qui n appartiennent pas au modele', function () {
    $article = Article::factory()->create();
    $a = mroMedia($article, 0);
    $b = mroMedia($article, 1);
    $other = Article::factory()->create();
    $foreign = mroMedia($other, 0);

    mroPost('article', $article->id, [$b->id, $foreign->id, $a->id])->assertOk();

    expect(mroOrders($article->fresh()))->toBe([$b->id => 0, $a->id => 1]);
    expect(mroOrders($other->fresh()))->toBe([$foreign->id => 0]);
});

it('reordonne un groupe sans deplacer les autres medias de la galerie', function () {
    $article = Article::factory()->create();
    $a = mroMedia($article, 0);
    $pdf = mroMedia($article, 1);
    $b = mroMedia($article, 2);

    mroPost('article', $article->id, [$b->id, $a->id])->assertOk();

    expect(mroOrders($article->fresh()))->toBe([$b->id => 0, $pdf->id => 1, $a->id => 2]);
});

it('renumerote de 0 a n-1 quand l ordre contenait des doublons', function () {
    $article = Article::factory()->create();
    $a = mroMedia($article, 0);
    $b = mroMedia($article, 2);
    $c = mroMedia($article, 2);

    mroPost('article', $article->id, [$c->id, $b->id])->assertOk();

    expect(mroOrders($article->fresh()))->toBe([$a->id => 0, $c->id => 1, $b->id => 2]);
});

it('reordonne aussi les medias d un diaporama', function () {
    $article = Article::factory()->create();
    $diaporama = Diaporama::create([
        'diaporamable_type' => Article::class,
        'diaporamable_id' => $article->id,
        'title' => 'Galerie',
        'order' => 0,
    ]);
    $a = mroMedia($diaporama, 0);
    $b = mroMedia($diaporama, 1);

    mroPost('diaporama', $diaporama->id, [$b->id, $a->id])->assertOk();

    expect(mroOrders($diaporama->fresh()))->toBe([$b->id => 0, $a->id => 1]);
});

it('refuse un visiteur et une liste de moins de deux medias', function () {
    $article = Article::factory()->create();
    $a = mroMedia($article, 0);

    $this->postJson(route('admin.media.reorder'), [
        'mediable_type' => 'article',
        'mediable_id' => $article->id,
        'ordered_ids' => [$a->id, $a->id],
    ])->assertUnauthorized();

    mroPost('article', $article->id, [$a->id])->assertUnprocessable()->assertJsonValidationErrors('ordered_ids');
});
