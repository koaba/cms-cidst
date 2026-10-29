<?php

use App\Models\Page;
use App\Models\PageBlock;
use App\Models\User;
use App\Services\PageBuilder\BlockSyncService;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Super Admin']);
    Role::firstOrCreate(['name' => 'Publication']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
    $this->page = Page::factory()->create();
});

function uiBlock(Page $page, string $type, array $data, int $order, array $extra = []): PageBlock
{
    return $page->blocks()->create(['type' => $type, 'data' => $data, 'order' => $order] + $extra);
}

it('affiche le builder avec les blocs racine dans l\'ordre', function () {
    $texte = uiBlock($this->page, 'texte', ['title' => 'Intro', 'content' => 'Corps'], 1);
    $cols = uiBlock($this->page, 'colonnes', ['column_count' => 2], 0);
    uiBlock($this->page, 'texte', ['content' => 'Enfant'], 0, ['parent_id' => $cols->id, 'slot_index' => 0]);

    $this->actingAs($this->admin)
        ->get(route('admin.pages.builder.show', $this->page))
        ->assertOk()
        ->assertViewIs('admin.pages.builder')
        ->assertSee('data-page-builder', false)
        ->assertViewHas('builder', function (array $builder) use ($texte, $cols) {
            expect(array_column($builder['blocks'], 'id'))->toBe([$cols->id, $texte->id]);
            expect($builder['saveUrl'])->toBe(route('admin.pages.builder.save', $this->page));
            expect($builder['classicUrl'])->toBe(route('admin.pages.blocks.index', $this->page));

            return true;
        });
});

it('n\'expose pas les donnees d\'un bloc verrouille', function () {
    $cols = uiBlock($this->page, 'colonnes', ['column_count' => 2], 0);
    uiBlock($this->page, 'texte', ['content' => 'Enfant'], 0, ['parent_id' => $cols->id, 'slot_index' => 0]);

    $this->actingAs($this->admin)
        ->get(route('admin.pages.builder.show', $this->page))
        ->assertViewHas('builder', function (array $builder) use ($cols) {
            $block = $builder['blocks'][0];

            expect($block['locked'])->toBeTrue();
            expect($block)->not->toHaveKey('data');
            expect($block['childrenCount'])->toBe(1);
            expect($block['editUrl'])->toBe(route('admin.pages.blocks.edit', [$this->page, $cols->id]));

            return true;
        });
});

it('expose les donnees d\'un bloc editable', function () {
    uiBlock($this->page, 'texte', ['title' => 'Intro', 'content' => 'Corps'], 0);

    $this->actingAs($this->admin)
        ->get(route('admin.pages.builder.show', $this->page))
        ->assertViewHas('builder', function (array $builder) {
            expect($builder['blocks'][0]['locked'])->toBeFalse();
            expect($builder['blocks'][0]['data']['content'])->toBe('Corps');

            return true;
        });
});

it('decrit les types editables, et indique ceux qui portent une image', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.pages.builder.show', $this->page))
        ->assertViewHas('builder', function (array $builder) {
            expect(array_keys($builder['types']))->toEqualCanonicalizing(BlockSyncService::editableTypes());
            expect($builder['types']['texte']['creatable'])->toBeTrue();
            expect($builder['types']['banniere_hero']['creatable'])->toBeTrue();
            expect($builder['types']['image']['media'])->toBeTrue();
            expect($builder['types']['texte']['label'])->toBe('Texte');

            return true;
        });
});

it('refuse un utilisateur sans role (403)', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.pages.builder.show', $this->page))
        ->assertForbidden();
});

it('redirige un visiteur non connecte', function () {
    $this->get(route('admin.pages.builder.show', $this->page))->assertRedirect();
});

it('autorise le role Publication', function () {
    $editor = User::factory()->create();
    $editor->assignRole('Publication');

    $this->actingAs($editor)
        ->get(route('admin.pages.builder.show', $this->page))
        ->assertOk();
});

it('est relie depuis l\'editeur classique', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.pages.blocks.index', $this->page))
        ->assertOk()
        ->assertSee(route('admin.pages.builder.show', $this->page), false);
});
