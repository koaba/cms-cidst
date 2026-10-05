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

it('cree un bloc colonnes avec un nombre de colonnes valide', function () {
    $page = Page::factory()->create();

    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'colonnes', 'title' => 'Mes colonnes', 'column_count' => '3']
    );

    $block = $page->blocks()->whereNull('parent_id')->first();
    expect($block->data['column_count'])->toBe(3);
    expect($block->data['column_count'])->toBeInt();
});

it('rejette un bloc colonnes avec moins de 2 colonnes', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'colonnes', 'column_count' => '1']
    );

    $response->assertSessionHasErrors('column_count');
});

it('rejette un bloc colonnes avec plus de 6 colonnes', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'colonnes', 'column_count' => '7']
    );

    $response->assertSessionHasErrors('column_count');
});

it('CARACTERISATION: column_count reste fige a la modification, meme si une autre valeur est envoyee', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'colonnes', 'column_count' => '3']
    );
    $block = $page->blocks()->whereNull('parent_id')->first();

    $this->actingAs($this->admin)->put(
        route('admin.pages.blocks.update', [$page, $block->id]),
        ['title' => 'Titre modifie', 'column_count' => '5']
    );

    $block->refresh();
    expect($block->data['column_count'])->toBe(3);
    expect($block->data['title'])->toBe('Titre modifie');
});

it('cree un bloc texte dans un slot de colonne valide', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'colonnes', 'column_count' => '3']
    );
    $parent = $page->blocks()->whereNull('parent_id')->first();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.columns.store', [$page, $parent->id, 1]),
        ['type' => 'texte', 'content' => 'Contenu de la colonne 1']
    );

    $response->assertRedirect(route('admin.pages.blocks.edit', [$page, $parent->id]));
    $child = $parent->childrenBySlot(1)->first();
    expect($child)->not->toBeNull();
    expect($child->type)->toBe('texte');
    expect($child->data['content'])->toBe('Contenu de la colonne 1');
});

it('rejette un slot_index hors bornes (superieur ou egal a column_count)', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'colonnes', 'column_count' => '3']
    );
    $parent = $page->blocks()->whereNull('parent_id')->first();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.columns.store', [$page, $parent->id, 3]),
        ['type' => 'texte', 'content' => 'Hors bornes']
    );

    $response->assertNotFound();
});

it('rejette un slot_index negatif', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'colonnes', 'column_count' => '3']
    );
    $parent = $page->blocks()->whereNull('parent_id')->first();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.columns.store', [$page, $parent->id, -1]),
        ['type' => 'texte', 'content' => 'Negatif']
    );

    $response->assertNotFound();
});

it('rejette la creation d\'un bloc colonnes dans une colonne', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'colonnes', 'column_count' => '3']
    );
    $parent = $page->blocks()->whereNull('parent_id')->first();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.columns.store', [$page, $parent->id, 0]),
        ['type' => 'colonnes', 'column_count' => '2']
    );

    $response->assertNotFound();
});

it('rejette la creation d\'un bloc accordeon dans une colonne', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'colonnes', 'column_count' => '3']
    );
    $parent = $page->blocks()->whereNull('parent_id')->first();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.columns.store', [$page, $parent->id, 0]),
        ['type' => 'accordeon', 'title' => 'Accordeon imbrique']
    );

    $response->assertNotFound();
});

it('supprime un bloc colonnes et nettoie recursivement les medias de ses enfants', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'colonnes', 'column_count' => '2']
    );
    $parent = $page->blocks()->whereNull('parent_id')->first();
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.columns.store', [$page, $parent->id, 0]),
        ['type' => 'texte', 'content' => 'A supprimer']
    );

    $this->actingAs($this->admin)->delete(route('admin.pages.blocks.destroy', [$page, $parent->id]));

    expect(PageBlock::find($parent->id))->toBeNull();
    expect(PageBlock::where('parent_id', $parent->id)->count())->toBe(0);
});
