<?php

use App\Models\Page;
use App\Models\PageBlock;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Super Admin']);
    Role::firstOrCreate(['name' => 'Publication']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
    $this->page = Page::factory()->create();
});

function builderBlock(Page $page, string $type, array $data, int $order, array $extra = []): PageBlock
{
    return $page->blocks()->create(['type' => $type, 'data' => $data, 'order' => $order] + $extra);
}

function builderSave($test, Page $page, array $blocks, ?User $user = null)
{
    return $test->actingAs($user ?? $test->admin)
        ->postJson(route('admin.pages.builder.save', $page), ['blocks' => $blocks]);
}

// -----------------------------------------------------------------
// Preservation de ce que le builder ne connait pas
// -----------------------------------------------------------------

it('preserve un bloc colonnes et ses enfants', function () {
    $texte = builderBlock($this->page, 'texte', ['title' => null, 'content' => 'A'], 0);
    $cols = builderBlock($this->page, 'colonnes', ['column_count' => 2], 1);
    $child = builderBlock($this->page, 'texte', ['content' => 'Enfant'], 0, ['parent_id' => $cols->id, 'slot_index' => 1]);

    builderSave($this, $this->page, [
        ['id' => $cols->id, 'type' => 'colonnes'],
        ['id' => $texte->id, 'type' => 'texte', 'data' => ['title' => null, 'content' => 'A2']],
    ])->assertOk();

    expect(PageBlock::find($child->id))->not->toBeNull();
    expect($child->fresh()->parent_id)->toBe($cols->id);
    expect($child->fresh()->slot_index)->toBe(1);
    expect($child->fresh()->data['content'])->toBe('Enfant');
    expect($cols->fresh()->data)->toBe(['column_count' => 2]);
    expect($cols->fresh()->order)->toBe(0);
    expect($texte->fresh()->order)->toBe(1);
    expect($texte->fresh()->data['content'])->toBe('A2');
});

it('ignore les donnees envoyees pour un bloc verrouille', function () {
    $cols = builderBlock($this->page, 'colonnes', ['column_count' => 2], 0);

    builderSave($this, $this->page, [
        ['id' => $cols->id, 'type' => 'colonnes', 'data' => ['column_count' => 9]],
    ])->assertOk();

    expect($cols->fresh()->data)->toBe(['column_count' => 2]);
});

it('conserve un bloc verrouille absent du payload et le place a la suite', function () {
    $a = builderBlock($this->page, 'texte', ['content' => 'A'], 0);
    $cols = builderBlock($this->page, 'colonnes', ['column_count' => 2], 1);
    $b = builderBlock($this->page, 'texte', ['content' => 'B'], 2);

    builderSave($this, $this->page, [
        ['id' => $b->id, 'type' => 'texte', 'data' => ['content' => 'B']],
        ['id' => $a->id, 'type' => 'texte', 'data' => ['content' => 'A']],
    ])->assertOk();

    expect($b->fresh()->order)->toBe(0);
    expect($a->fresh()->order)->toBe(1);
    expect(PageBlock::find($cols->id))->not->toBeNull();
    expect($cols->fresh()->order)->toBe(2);
});

// -----------------------------------------------------------------
// Creation, modification, suppression
// -----------------------------------------------------------------

it('cree un bloc racine sans id', function () {
    builderSave($this, $this->page, [
        ['type' => 'texte', 'data' => ['content' => 'Nouveau']],
    ])->assertOk()->assertJsonPath('blocks.0.type', 'texte');

    $block = $this->page->blocks()->first();
    expect($block->parent_id)->toBeNull();
    expect($block->order)->toBe(0);
    expect($block->data['content'])->toBe('Nouveau');
});

it('supprime un bloc editable absent du payload', function () {
    $a = builderBlock($this->page, 'texte', ['content' => 'A'], 0);
    $b = builderBlock($this->page, 'texte', ['content' => 'B'], 1);

    builderSave($this, $this->page, [
        ['id' => $b->id, 'type' => 'texte', 'data' => ['content' => 'B']],
    ])->assertOk();

    $this->assertDatabaseMissing('page_blocks', ['id' => $a->id]);
    expect($b->fresh()->order)->toBe(0);
});

it('applique les casts du type (banniere_hero)', function () {
    $hero = builderBlock($this->page, 'banniere_hero', ['titre' => 'Ancien', 'overlay_opacity' => 40], 0);

    builderSave($this, $this->page, [
        ['id' => $hero->id, 'type' => 'banniere_hero', 'data' => ['titre' => 'Nouveau', 'overlay_opacity' => '55']],
    ])->assertOk();

    expect($hero->fresh()->data['titre'])->toBe('Nouveau');
    expect($hero->fresh()->data['overlay_opacity'])->toBe(55);
});

it('refuse de creer une banniere_hero sans image (limite de la v1)', function () {
    builderSave($this, $this->page, [
        ['type' => 'banniere_hero', 'data' => ['titre' => 'Sans image']],
    ])->assertUnprocessable()->assertJsonValidationErrors('blocks.0.data.image');

    expect($this->page->blocks()->count())->toBe(0);
});

// -----------------------------------------------------------------
// Refus : rien n'est ecrit
// -----------------------------------------------------------------

it('refuse l\'id d\'un bloc d\'une autre page sans rien ecrire', function () {
    $other = Page::factory()->create();
    $foreign = builderBlock($other, 'texte', ['content' => 'Autre'], 0);
    $mine = builderBlock($this->page, 'texte', ['content' => 'Mien'], 0);

    builderSave($this, $this->page, [
        ['id' => $mine->id, 'type' => 'texte', 'data' => ['content' => 'Modifie']],
        ['id' => $foreign->id, 'type' => 'texte', 'data' => ['content' => 'Pirate']],
    ])->assertUnprocessable()->assertJsonValidationErrors('blocks.1.id');

    expect($foreign->fresh()->data['content'])->toBe('Autre');
    expect($mine->fresh()->data['content'])->toBe('Mien');
});

it('refuse l\'id d\'un bloc enfant', function () {
    $cols = builderBlock($this->page, 'colonnes', ['column_count' => 2], 0);
    $child = builderBlock($this->page, 'texte', ['content' => 'Enfant'], 0, ['parent_id' => $cols->id, 'slot_index' => 0]);

    builderSave($this, $this->page, [
        ['id' => $child->id, 'type' => 'texte', 'data' => ['content' => 'Pirate']],
    ])->assertUnprocessable()->assertJsonValidationErrors('blocks.0.id');

    expect($child->fresh()->data['content'])->toBe('Enfant');
});

it('refuse un id en double', function () {
    $a = builderBlock($this->page, 'texte', ['content' => 'A'], 0);

    builderSave($this, $this->page, [
        ['id' => $a->id, 'type' => 'texte', 'data' => ['content' => 'A']],
        ['id' => $a->id, 'type' => 'texte', 'data' => ['content' => 'A']],
    ])->assertUnprocessable()->assertJsonValidationErrors('blocks.1.id');
});

it('n\'ecrit rien si un seul bloc est invalide', function () {
    $a = builderBlock($this->page, 'texte', ['content' => 'A'], 0);
    $b = builderBlock($this->page, 'texte', ['content' => 'B'], 1);

    builderSave($this, $this->page, [
        ['id' => $b->id, 'type' => 'texte', 'data' => ['content' => 'B2']],
        ['id' => $a->id, 'type' => 'texte', 'data' => ['content' => '']],
    ])->assertUnprocessable()->assertJsonValidationErrors('blocks.1.data.content');

    expect($a->fresh()->data['content'])->toBe('A');
    expect($b->fresh()->data['content'])->toBe('B');
    expect($b->fresh()->order)->toBe(1);
});

it('refuse de changer le type d\'un bloc existant', function () {
    $a = builderBlock($this->page, 'texte', ['content' => 'A'], 0);

    builderSave($this, $this->page, [
        ['id' => $a->id, 'type' => 'bouton', 'data' => ['label' => 'x', 'url' => '/x']],
    ])->assertUnprocessable()->assertJsonValidationErrors('blocks.0.type');

    expect($a->fresh()->type)->toBe('texte');
});

it('refuse de creer un type non editable dans le builder', function () {
    builderSave($this, $this->page, [
        ['type' => 'colonnes', 'data' => ['column_count' => 2]],
    ])->assertUnprocessable()->assertJsonValidationErrors('blocks.0.type');

    expect($this->page->blocks()->count())->toBe(0);
});

// -----------------------------------------------------------------
// Acces
// -----------------------------------------------------------------

it('refuse un utilisateur sans role (403)', function () {
    $user = User::factory()->create();

    builderSave($this, $this->page, [], $user)->assertForbidden();
});

it('refuse un visiteur non connecte (401)', function () {
    $this->postJson(route('admin.pages.builder.save', $this->page), ['blocks' => []])->assertUnauthorized();
});

it('autorise le role Publication', function () {
    $editor = User::factory()->create();
    $editor->assignRole('Publication');

    builderSave($this, $this->page, [
        ['type' => 'texte', 'data' => ['content' => 'Par Publication']],
    ], $editor)->assertOk();
});
