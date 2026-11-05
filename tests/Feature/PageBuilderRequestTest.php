<?php

use App\Models\Page;

beforeEach(function () {
    $this->page = Page::factory()->create();

    $this->save = fn (array $payload) => $this->actingAs(adminUser())
        ->postJson(route('admin.pages.builder.save', $this->page), $payload);
});

it('refuse une enveloppe invalide avant toute synchronisation', function (array $payload, string $field) {
    ($this->save)($payload)->assertUnprocessable()->assertJsonValidationErrors($field);

    expect($this->page->blocks()->count())->toBe(0);
})->with([
    'sans liste de blocs' => [[], 'blocks'],
    'blocs non tableau' => [['blocks' => 'texte'], 'blocks'],
    'plus de 200 blocs' => [['blocks' => array_fill(0, 201, ['type' => 'texte'])], 'blocks'],
    'bloc non tableau' => [['blocks' => ['texte']], 'blocks.0'],
    'bloc sans type' => [['blocks' => [['data' => []]]], 'blocks.0.type'],
    'donnees non tableau' => [['blocks' => [['type' => 'texte', 'data' => 'x']]], 'blocks.0.data'],
    'identifiant non entier' => [['blocks' => [['id' => 'abc', 'type' => 'texte']]], 'blocks.0.id'],
    'version non texte' => [['blocks' => [], 'version' => ['x']], 'version'],
]);

it('accepte une liste de blocs vide', function () {
    ($this->save)(['blocks' => []])->assertOk()->assertJsonPath('success', true);
});
