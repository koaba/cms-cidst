<?php

use App\Models\Article;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

function pmMedia(string $name): Media
{
    UploadedFile::fake()->image($name)->storeAs('media', $name, 'public');

    return Media::factory()->create(['path' => 'media/'.$name]);
}

it('supprime un media lie a aucun contenu ainsi que son fichier', function () {
    $orphan = pmMedia('orphelin.jpg');

    $this->artisan('media:prune')
        ->expectsOutputToContain('1 média(s) orphelin(s) supprimé(s).')
        ->assertExitCode(0);

    expect(Media::find($orphan->id))->toBeNull();
    Storage::disk('public')->assertMissing('media/orphelin.jpg');
});

it('conserve un media lie a un contenu', function () {
    $used = pmMedia('utilise.jpg');
    $article = Article::factory()->create();
    $article->media()->attach($used->id, ['order' => 0]);

    $this->artisan('media:prune')
        ->expectsOutput('Aucun média orphelin à supprimer.')
        ->assertExitCode(0);

    expect(Media::find($used->id))->not->toBeNull();
    Storage::disk('public')->assertExists('media/utilise.jpg');
});

it('ne supprime que les orphelins quand les deux cas coexistent', function () {
    $used = pmMedia('utilise.jpg');
    $orphan = pmMedia('orphelin.jpg');
    Article::factory()->create()->media()->attach($used->id, ['order' => 0]);

    $this->artisan('media:prune')->assertExitCode(0);

    expect(Media::pluck('id')->all())->toBe([$used->id])
        ->and(Media::find($orphan->id))->toBeNull();
});

it('ne fait rien sur une mediatheque vide', function () {
    $this->artisan('media:prune')
        ->expectsOutput('Aucun média orphelin à supprimer.')
        ->assertExitCode(0);
});
