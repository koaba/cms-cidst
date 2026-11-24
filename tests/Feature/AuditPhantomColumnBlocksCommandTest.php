<?php

use App\Models\Media;
use App\Models\Page;
use App\Models\PageBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

function apBlock(array $attributes = []): PageBlock
{
    return PageBlock::create($attributes + [
        'page_id' => Page::factory()->create()->id,
        'type' => 'colonnes',
        'data' => [],
        'order' => 0,
    ]);
}

function apBackup(PageBlock $block): string
{
    return storage_path("app/backups/phantom-block-{$block->id}.json");
}

afterEach(function () {
    foreach (glob(storage_path('app/backups/phantom-block-*.json')) ?: [] as $file) {
        File::delete($file);
    }
});

it('signale un bloc vide comme candidat sans rien supprimer en audit', function () {
    $block = apBlock();

    $this->artisan('blocks:audit-phantom-columns', ['ids' => [$block->id]])
        ->expectsOutputToContain('Candidat sûr à la suppression')
        ->assertExitCode(0);

    expect(PageBlock::find($block->id))->not->toBeNull()
        ->and(file_exists(apBackup($block)))->toBeFalse();
});

it('sauvegarde puis supprime un bloc vide avec --fix', function () {
    $block = apBlock();

    $this->artisan('blocks:audit-phantom-columns', ['ids' => [$block->id], '--fix' => true])
        ->expectsOutputToContain("Bloc #{$block->id} sauvegardé puis supprimé.")
        ->assertExitCode(0);

    expect(PageBlock::find($block->id))->toBeNull()
        ->and(file_exists(apBackup($block)))->toBeTrue()
        ->and(json_decode(file_get_contents(apBackup($block)), true)['id'])->toBe($block->id);
});

it('refuse de supprimer un bloc avec des colonnes legacy', function () {
    $block = apBlock(['data' => ['columns' => [['content' => 'ancien']]]]);

    $this->artisan('blocks:audit-phantom-columns', ['ids' => [$block->id], '--fix' => true])
        ->expectsOutputToContain('Contient du contenu réel')
        ->assertExitCode(0);

    expect(PageBlock::find($block->id))->not->toBeNull()
        ->and(file_exists(apBackup($block)))->toBeFalse();
});

it('refuse de supprimer un bloc qui a des enfants', function () {
    $parent = apBlock();
    apBlock(['page_id' => $parent->page_id, 'parent_id' => $parent->id, 'slot_index' => 0, 'type' => 'texte']);

    $this->artisan('blocks:audit-phantom-columns', ['ids' => [$parent->id], '--fix' => true])
        ->expectsOutputToContain('Contient du contenu réel')
        ->assertExitCode(0);

    expect(PageBlock::find($parent->id))->not->toBeNull();
});

it('refuse de supprimer un bloc avec un media attache', function () {
    $block = apBlock();
    $block->media()->attach(Media::factory()->create()->id, ['order' => 0]);

    $this->artisan('blocks:audit-phantom-columns', ['ids' => [$block->id], '--fix' => true])
        ->expectsOutputToContain('Contient du contenu réel')
        ->assertExitCode(0);

    expect(PageBlock::find($block->id))->not->toBeNull();
});

it('ne touche que les ids demandes', function () {
    $target = apBlock();
    $other = apBlock(['page_id' => $target->page_id]);

    $this->artisan('blocks:audit-phantom-columns', ['ids' => [$target->id], '--fix' => true])
        ->assertExitCode(0);

    expect(PageBlock::find($target->id))->toBeNull()
        ->and(PageBlock::find($other->id))->not->toBeNull();
});
