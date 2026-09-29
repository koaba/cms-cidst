<?php

use App\Models\Page;
use App\Models\PageBlock;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
});

// -----------------------------------------------------------------
// reorder() - blocs racine
// -----------------------------------------------------------------

it('reordonne les blocs racine d\'une page', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.store', $page), ['type' => 'texte', 'content' => 'A']);
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.store', $page), ['type' => 'texte', 'content' => 'B']);
    $blocks = $page->blocks()->whereNull('parent_id')->orderBy('order')->get();
    $blockA = $blocks[0];
    $blockB = $blocks[1];

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.reorder', $page),
        ['order' => [$blockB->id, $blockA->id]]
    );

    $response->assertJson(['success' => true]);
    expect($blockB->fresh()->order)->toBe(0);
    expect($blockA->fresh()->order)->toBe(1);
});

it('rejette un reorder avec un id de bloc inexistant', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.reorder', $page),
        ['order' => [99999]]
    );

    $response->assertSessionHasErrors('order.0');
});

it('CARACTERISATION: reorder ne verifie pas que les blocs appartiennent bien a la page cible', function () {
    // La requete filtre par page_id ET id dans la boucle update(), donc un
    // id d'un bloc d'une AUTRE page est simplement ignore silencieusement
    // (aucune ligne affectee), pas une faille de securite en soi, mais pas
    // d'erreur renvoyee non plus si l'ordre demande ne peut pas etre applique.
    $pageA = Page::factory()->create();
    $pageB = Page::factory()->create();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.store', $pageB), ['type' => 'texte', 'content' => 'Bloc de B']);
    $blockOfB = $pageB->blocks()->whereNull('parent_id')->first();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.reorder', $pageA),
        ['order' => [$blockOfB->id]]
    );

    $response->assertJson(['success' => true]);
    expect($blockOfB->fresh()->order)->not->toBe(0);
});

// -----------------------------------------------------------------
// updateChild() / destroyChild() - enfant de colonne
// -----------------------------------------------------------------

it('modifie un enfant de colonne existant', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.store', $page), ['type' => 'colonnes', 'column_count' => '2']);
    $parent = $page->blocks()->whereNull('parent_id')->first();
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.columns.store', [$page, $parent->id, 0]),
        ['type' => 'texte', 'content' => 'Avant']
    );
    $child = $parent->childrenBySlot(0)->first();

    $response = $this->actingAs($this->admin)->put(
        route('admin.pages.blocks.columns.update', [$page, $parent->id, 0, $child->id]),
        ['content' => 'Apres']
    );

    $response->assertRedirect(route('admin.pages.blocks.edit', [$page, $parent->id]));
    expect($child->fresh()->data['content'])->toBe('Apres');
});

it('rejette la modification d\'un enfant de colonne avec un slot_index hors bornes', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.store', $page), ['type' => 'colonnes', 'column_count' => '2']);
    $parent = $page->blocks()->whereNull('parent_id')->first();
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.columns.store', [$page, $parent->id, 0]),
        ['type' => 'texte', 'content' => 'Contenu']
    );
    $child = $parent->childrenBySlot(0)->first();

    $response = $this->actingAs($this->admin)->put(
        route('admin.pages.blocks.columns.update', [$page, $parent->id, 5, $child->id]),
        ['content' => 'Modifie']
    );

    $response->assertNotFound();
});

it('supprime un enfant de colonne et nettoie son media', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.store', $page), ['type' => 'colonnes', 'column_count' => '2']);
    $parent = $page->blocks()->whereNull('parent_id')->first();
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.columns.store', [$page, $parent->id, 0]),
        ['type' => 'texte', 'content' => 'A supprimer']
    );
    $child = $parent->childrenBySlot(0)->first();

    $response = $this->actingAs($this->admin)->delete(
        route('admin.pages.blocks.columns.destroy', [$page, $parent->id, 0, $child->id])
    );

    $response->assertRedirect(route('admin.pages.blocks.edit', [$page, $parent->id]));
    expect(PageBlock::find($child->id))->toBeNull();
});

// -----------------------------------------------------------------
// updateItemContent() / destroyItemContent() - contenu d'item d'accordeon
// -----------------------------------------------------------------

it('modifie le contenu d\'un item d\'accordeon existant', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.store', $page), ['type' => 'accordeon']);
    $parent = $page->blocks()->whereNull('parent_id')->first();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.items.store', [$page, $parent->id]), ['title' => 'Item 1']);
    $item = $parent->children()->where('type', 'accordeon_item')->first();
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.items.content.store', [$page, $parent->id, $item->id]),
        ['type' => 'texte', 'content' => 'Avant']
    );
    $content = $item->children()->first();

    $response = $this->actingAs($this->admin)->put(
        route('admin.pages.blocks.items.content.update', [$page, $parent->id, $item->id, $content->id]),
        ['content' => 'Apres']
    );

    $response->assertRedirect(route('admin.pages.blocks.items.edit', [$page, $parent->id, $item->id]));
    expect($content->fresh()->data['content'])->toBe('Apres');
});

it('supprime le contenu d\'un item d\'accordeon et nettoie son media recursivement', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.store', $page), ['type' => 'accordeon']);
    $parent = $page->blocks()->whereNull('parent_id')->first();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.items.store', [$page, $parent->id]), ['title' => 'Item 1']);
    $item = $parent->children()->where('type', 'accordeon_item')->first();
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.items.content.store', [$page, $parent->id, $item->id]),
        ['type' => 'texte', 'content' => 'A supprimer']
    );
    $content = $item->children()->first();

    $response = $this->actingAs($this->admin)->delete(
        route('admin.pages.blocks.items.content.destroy', [$page, $parent->id, $item->id, $content->id])
    );

    $response->assertRedirect(route('admin.pages.blocks.items.edit', [$page, $parent->id, $item->id]));
    expect(PageBlock::find($content->id))->toBeNull();
});

it('rejette la modification du contenu d\'un item d\'accordeon appartenant a un autre item', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.store', $page), ['type' => 'accordeon']);
    $parent = $page->blocks()->whereNull('parent_id')->first();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.items.store', [$page, $parent->id]), ['title' => 'Item 1']);
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.items.store', [$page, $parent->id]), ['title' => 'Item 2']);
    $items = $parent->children()->where('type', 'accordeon_item')->orderBy('slot_index')->get();
    $itemA = $items[0];
    $itemB = $items[1];
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.items.content.store', [$page, $parent->id, $itemA->id]),
        ['type' => 'texte', 'content' => 'Contenu de A']
    );
    $contentOfA = $itemA->children()->first();

    $response = $this->actingAs($this->admin)->put(
        route('admin.pages.blocks.items.content.update', [$page, $parent->id, $itemB->id, $contentOfA->id]),
        ['content' => 'Tentative via item B']
    );

    $response->assertNotFound();
});