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

it('cree un bloc accordeon avec un titre optionnel', function () {
    $page = Page::factory()->create();

    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'accordeon', 'title' => 'Mon accordeon']
    );

    $block = $page->blocks()->whereNull('parent_id')->first();
    expect($block->type)->toBe('accordeon');
    expect($block->data['title'])->toBe('Mon accordeon');
});

it('ajoute un item a un accordeon avec un slot_index incremental', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.store', $page), ['type' => 'accordeon']);
    $parent = $page->blocks()->whereNull('parent_id')->first();

    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.items.store', [$page, $parent->id]),
        ['title' => 'Item 1']
    );
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.items.store', [$page, $parent->id]),
        ['title' => 'Item 2']
    );

    $items = $parent->children()->where('type', 'accordeon_item')->orderBy('slot_index')->get();
    expect($items)->toHaveCount(2);
    expect($items[0]->slot_index)->toBe(0);
    expect($items[1]->slot_index)->toBe(1);
});

it('rejette un item d\'accordeon sans titre', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.store', $page), ['type' => 'accordeon']);
    $parent = $page->blocks()->whereNull('parent_id')->first();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.items.store', [$page, $parent->id]),
        []
    );

    $response->assertSessionHasErrors('title');
});

it('reordonne les items d\'un accordeon', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.store', $page), ['type' => 'accordeon']);
    $parent = $page->blocks()->whereNull('parent_id')->first();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.items.store', [$page, $parent->id]), ['title' => 'A']);
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.items.store', [$page, $parent->id]), ['title' => 'B']);
    $items = $parent->children()->where('type', 'accordeon_item')->orderBy('slot_index')->get();
    $itemA = $items[0];
    $itemB = $items[1];

    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.items.reorder', [$page, $parent->id]),
        ['order' => [$itemB->id, $itemA->id]]
    );

    expect($itemB->fresh()->slot_index)->toBe(0);
    expect($itemA->fresh()->slot_index)->toBe(1);
});

it('ajoute du contenu texte a un item d\'accordeon', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.store', $page), ['type' => 'accordeon']);
    $parent = $page->blocks()->whereNull('parent_id')->first();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.items.store', [$page, $parent->id]), ['title' => 'Item 1']);
    $item = $parent->children()->where('type', 'accordeon_item')->first();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.items.content.store', [$page, $parent->id, $item->id]),
        ['type' => 'texte', 'content' => 'Contenu de l\'item']
    );

    $response->assertRedirect(route('admin.pages.blocks.items.edit', [$page, $parent->id, $item->id]));
    $content = $item->children()->first();
    expect($content->type)->toBe('texte');
    expect($content->data['content'])->toBe('Contenu de l\'item');
});

it('CARACTERISATION: slot_index du contenu d\'un item d\'accordeon reste null, contrairement au slot_index de l\'item lui-meme', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.store', $page), ['type' => 'accordeon']);
    $parent = $page->blocks()->whereNull('parent_id')->first();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.items.store', [$page, $parent->id]), ['title' => 'Item 1']);
    $item = $parent->children()->where('type', 'accordeon_item')->first();

    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.items.content.store', [$page, $parent->id, $item->id]),
        ['type' => 'texte', 'content' => 'Contenu']
    );

    // storeItemContent() cree l'enfant sans jamais fixer slot_index,
    // contrairement a storeAccordionItem() qui le fixe explicitement.
    // childrenGroupedBySlot() regrouperait donc tout contenu d'item sous
    // la cle null si on l'utilisait ici (elle ne l'est pas actuellement).
    expect($item->slot_index)->toBe(0);
    expect($item->children()->first()->slot_index)->toBeNull();
});

it('rejette la creation d\'un bloc colonnes comme contenu d\'un item d\'accordeon', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.store', $page), ['type' => 'accordeon']);
    $parent = $page->blocks()->whereNull('parent_id')->first();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.items.store', [$page, $parent->id]), ['title' => 'Item 1']);
    $item = $parent->children()->where('type', 'accordeon_item')->first();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.items.content.store', [$page, $parent->id, $item->id]),
        ['type' => 'colonnes', 'column_count' => '2']
    );

    $response->assertNotFound();
});

it('supprime un item d\'accordeon et nettoie recursivement le contenu (media inclus)', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.store', $page), ['type' => 'accordeon']);
    $parent = $page->blocks()->whereNull('parent_id')->first();
    $this->actingAs($this->admin)->post(route('admin.pages.blocks.items.store', [$page, $parent->id]), ['title' => 'Item 1']);
    $item = $parent->children()->where('type', 'accordeon_item')->first();
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.items.content.store', [$page, $parent->id, $item->id]),
        ['type' => 'texte', 'content' => 'A supprimer']
    );

    $this->actingAs($this->admin)->delete(route('admin.pages.blocks.items.destroy', [$page, $parent->id, $item->id]));

    expect(PageBlock::find($item->id))->toBeNull();
    expect(PageBlock::where('parent_id', $item->id)->count())->toBe(0);
});