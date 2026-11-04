<?php

use App\Models\Page;
use App\Models\PageBlock;
use App\Services\PageBuilder\BlockSyncService;
use App\Services\PageBuilder\StaleBuilderException;
use Illuminate\Validation\ValidationException;

/*
 * BlockSyncService::sync doit etre atomique : si quoi que ce soit echoue, la page
 * retrouve exactement son etat d'avant (blocs, ordre, donnees). Chaque test compare
 * un instantane de la base avant et apres l'echec.
 */

beforeEach(function () {
    $this->page = Page::factory()->create();
    $this->texte = $this->page->blocks()->create(['type' => 'texte', 'data' => ['content' => 'Texte initial'], 'order' => 3]);
    $this->bouton = $this->page->blocks()->create(['type' => 'bouton', 'data' => ['label' => 'Bouton initial', 'url' => '/a'], 'order' => 7]);

    $this->snapshot = fn () => $this->page->blocks()->orderBy('id')->get(['id', 'type', 'order', 'data'])->toArray();
    $this->sync = fn (array $items, ?string $version = null) => app(BlockSyncService::class)->sync($this->page, $items, $version);
});

it('enregistre une sauvegarde valide : donnees, ordre et suppression (temoin)', function () {
    $result = ($this->sync)([
        ['id' => $this->bouton->id, 'type' => 'bouton', 'data' => ['label' => 'Bouton modifie', 'url' => '/b']],
    ]);

    expect(collect($result)->pluck('id')->all())->toBe([$this->bouton->id])
        ->and(PageBlock::find($this->bouton->id)->data['label'])->toBe('Bouton modifie')
        ->and(PageBlock::find($this->bouton->id)->order)->toBe(0)
        ->and(PageBlock::find($this->texte->id))->toBeNull();
});

it('n ecrit rien quand un bloc tardif du payload est invalide', function () {
    $before = ($this->snapshot)();

    expect(fn () => ($this->sync)([
        ['id' => $this->bouton->id, 'type' => 'bouton', 'data' => ['label' => 'Modifie', 'url' => '/b']],
        ['id' => $this->texte->id, 'type' => 'texte', 'data' => ['content' => 'Autre']],
        ['type' => 'bouton', 'data' => ['label' => '', 'url' => '']],
    ]))->toThrow(ValidationException::class);

    expect(($this->snapshot)())->toEqual($before);
});

it('annule tout quand l ecriture echoue apres des modifications et une creation', function () {
    $before = ($this->snapshot)();
    PageBlock::created(function (PageBlock $block) {
        throw new RuntimeException('echec simule a la creation');
    });

    expect(fn () => ($this->sync)([
        ['id' => $this->bouton->id, 'type' => 'bouton', 'data' => ['label' => 'Modifie', 'url' => '/b']],
        ['id' => $this->texte->id, 'type' => 'texte', 'data' => ['content' => 'Autre']],
        ['type' => 'bouton', 'data' => ['label' => 'Nouveau', 'url' => '/c']],
    ]))->toThrow(RuntimeException::class, 'echec simule a la creation');

    expect(($this->snapshot)())->toEqual($before);
});

it('annule tout quand la suppression d un bloc echoue apres les modifications', function () {
    $before = ($this->snapshot)();
    PageBlock::deleted(function (PageBlock $block) {
        throw new RuntimeException('echec simule a la suppression');
    });

    expect(fn () => ($this->sync)([
        ['id' => $this->bouton->id, 'type' => 'bouton', 'data' => ['label' => 'Modifie', 'url' => '/b']],
    ]))->toThrow(RuntimeException::class, 'echec simule a la suppression');

    expect(($this->snapshot)())->toEqual($before)
        ->and(PageBlock::find($this->texte->id))->not->toBeNull();
});

it('refuse une sauvegarde depuis un builder perime sans rien ecrire', function () {
    $staleVersion = app(BlockSyncService::class)->version($this->page);
    $this->texte->update(['data' => ['content' => 'Modifie ailleurs']]);
    $before = ($this->snapshot)();

    expect(fn () => ($this->sync)([
        ['id' => $this->bouton->id, 'type' => 'bouton', 'data' => ['label' => 'Modifie', 'url' => '/b']],
    ], $staleVersion))->toThrow(StaleBuilderException::class);

    expect(($this->snapshot)())->toEqual($before);
});
