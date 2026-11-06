<?php

use App\Models\Page;
use App\Models\PageBlock;

dataset('suppressions de blocs', [
    'bloc de premier niveau',
    'enfant de colonne',
    'item d accordeon',
    'contenu d item d accordeon',
]);

beforeEach(function () {
    // Cree la cible comme Super Admin (memes appels que les tests existants du builder),
    // puis renvoie la route de suppression, la page qui porte le bouton et l'id du bloc.
    $this->makeTarget = function (string $kind): array {
        $page = Page::factory()->create();
        $admin = adminUser();
        $post = fn (string $route, array $params, array $data) => $this->actingAs($admin)->post(route($route, $params), $data);

        if ($kind === 'bloc de premier niveau') {
            $post('admin.pages.blocks.store', [$page], ['type' => 'texte', 'content' => 'A']);
            $block = $page->blocks()->whereNull('parent_id')->first();

            return [
                'destroy' => ['admin.pages.blocks.destroy', [$page, $block->id]],
                'view' => ['admin.pages.blocks.index', [$page]],
                'id' => $block->id,
            ];
        }

        if ($kind === 'enfant de colonne') {
            $post('admin.pages.blocks.store', [$page], ['type' => 'colonnes', 'column_count' => '2']);
            $parent = $page->blocks()->whereNull('parent_id')->first();
            $post('admin.pages.blocks.columns.store', [$page, $parent->id, 0], ['type' => 'texte', 'content' => 'A']);
            $child = $parent->childrenBySlot(0)->first();

            return [
                'destroy' => ['admin.pages.blocks.columns.destroy', [$page, $parent->id, 0, $child->id]],
                'view' => ['admin.pages.blocks.edit', [$page, $parent->id]],
                'id' => $child->id,
            ];
        }

        $post('admin.pages.blocks.store', [$page], ['type' => 'accordeon']);
        $parent = $page->blocks()->whereNull('parent_id')->first();
        $post('admin.pages.blocks.items.store', [$page, $parent->id], ['title' => 'Item 1']);
        $item = $parent->children()->where('type', 'accordeon_item')->first();

        if ($kind === 'item d accordeon') {
            return [
                'destroy' => ['admin.pages.blocks.items.destroy', [$page, $parent->id, $item->id]],
                'view' => ['admin.pages.blocks.edit', [$page, $parent->id]],
                'id' => $item->id,
            ];
        }

        $post('admin.pages.blocks.items.content.store', [$page, $parent->id, $item->id], ['type' => 'texte', 'content' => 'A']);
        $content = $item->children()->first();

        return [
            'destroy' => ['admin.pages.blocks.items.content.destroy', [$page, $parent->id, $item->id, $content->id]],
            'view' => ['admin.pages.blocks.items.edit', [$page, $parent->id, $item->id]],
            'id' => $content->id,
        ];
    };
});

it('refuse la suppression sans code a Publication', function (string $kind) {
    setDeleteCode('4242');
    $target = ($this->makeTarget)($kind);

    $this->actingAs(publicationUser())->delete(route(...$target['destroy']))
        ->assertRedirect()
        ->assertSessionHasErrors('delete_code');

    expect(PageBlock::find($target['id']))->not->toBeNull();
})->with('suppressions de blocs');

it('refuse la suppression avec un mauvais code a Publication', function (string $kind) {
    setDeleteCode('4242');
    $target = ($this->makeTarget)($kind);

    $this->actingAs(publicationUser())->delete(route(...$target['destroy']), ['delete_code' => '0000'])
        ->assertRedirect()
        ->assertSessionHasErrors('delete_code');

    expect(PageBlock::find($target['id']))->not->toBeNull();
})->with('suppressions de blocs');

it('supprime avec le bon code pour Publication', function (string $kind) {
    setDeleteCode('4242');
    $target = ($this->makeTarget)($kind);

    $this->actingAs(publicationUser())->delete(route(...$target['destroy']), ['delete_code' => '4242'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(PageBlock::find($target['id']))->toBeNull();
})->with('suppressions de blocs');

it('exempte le Super Admin du code', function (string $kind) {
    setDeleteCode('4242');
    $target = ($this->makeTarget)($kind);

    $this->actingAs(adminUser())->delete(route(...$target['destroy']))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(PageBlock::find($target['id']))->toBeNull();
})->with('suppressions de blocs');

it('ajoute le champ code a la page du bouton pour Publication seulement', function (string $kind) {
    $target = ($this->makeTarget)($kind);

    $this->actingAs(publicationUser())->get(route(...$target['view']))
        ->assertOk()
        ->assertSee('name="delete_code"', false);

    $this->actingAs(adminUser())->get(route(...$target['view']))
        ->assertOk()
        ->assertDontSee('name="delete_code"', false);
})->with('suppressions de blocs');
