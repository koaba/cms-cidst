<?php

use App\Models\Page;
use App\Models\PageBlock;

beforeEach(function () {
    setDeleteCode('4242');

    $this->page = Page::factory()->create();
    $this->keep = $this->page->blocks()->create(['type' => 'texte', 'data' => ['title' => null, 'content' => 'A'], 'order' => 0]);
    $this->doomed = $this->page->blocks()->create(['type' => 'texte', 'data' => ['title' => null, 'content' => 'B'], 'order' => 1]);

    // Enregistre la page en ne gardant que le premier bloc : le second est donc supprime.
    $this->saveWithoutDoomed = fn ($user, array $extra = []) => $this->actingAs($user)->postJson(
        route('admin.pages.builder.save', $this->page),
        ['blocks' => [['id' => $this->keep->id, 'type' => 'texte', 'data' => ['title' => null, 'content' => 'A']]]] + $extra
    );
});

it('refuse a Publication de supprimer un bloc sans code', function () {
    ($this->saveWithoutDoomed)(publicationUser())
        ->assertStatus(422)
        ->assertJsonValidationErrors('delete_code');

    expect(PageBlock::find($this->doomed->id))->not->toBeNull();
});

it('refuse a Publication de supprimer un bloc avec un mauvais code', function () {
    ($this->saveWithoutDoomed)(publicationUser(), ['delete_code' => '0000'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('delete_code');

    expect(PageBlock::find($this->doomed->id))->not->toBeNull();
});

it('supprime le bloc pour Publication avec le bon code', function () {
    ($this->saveWithoutDoomed)(publicationUser(), ['delete_code' => '4242'])->assertOk();

    expect(PageBlock::find($this->doomed->id))->toBeNull();
    expect(PageBlock::find($this->keep->id))->not->toBeNull();
});

it('ne demande aucun code a Publication quand rien n est supprime', function () {
    $this->actingAs(publicationUser())->postJson(route('admin.pages.builder.save', $this->page), [
        'blocks' => [
            ['id' => $this->doomed->id, 'type' => 'texte', 'data' => ['title' => null, 'content' => 'B2']],
            ['id' => $this->keep->id, 'type' => 'texte', 'data' => ['title' => null, 'content' => 'A2']],
        ],
    ])->assertOk();

    expect(PageBlock::find($this->doomed->id)->data['content'])->toBe('B2');
});

it('exempte le Super Admin du code dans le builder', function () {
    ($this->saveWithoutDoomed)(adminUser())->assertOk();

    expect(PageBlock::find($this->doomed->id))->toBeNull();
});

it('ne demande pas de code pour un bloc verrouille absent du payload, car il est conserve', function () {
    $cols = $this->page->blocks()->create(['type' => 'colonnes', 'data' => ['column_count' => 2], 'order' => 2]);

    $this->actingAs(publicationUser())->postJson(route('admin.pages.builder.save', $this->page), [
        'blocks' => [
            ['id' => $this->keep->id, 'type' => 'texte', 'data' => ['title' => null, 'content' => 'A']],
            ['id' => $this->doomed->id, 'type' => 'texte', 'data' => ['title' => null, 'content' => 'B']],
        ],
    ])->assertOk();

    expect(PageBlock::find($cols->id))->not->toBeNull();
});
