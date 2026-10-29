<?php

use App\Models\Page;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
    $this->page = Page::factory()->create();
});

function designSave($test, array $blocks)
{
    return $test->actingAs($test->admin)
        ->postJson(route('admin.pages.builder.save', $test->page), ['blocks' => $blocks]);
}

it('refuse un alignement invalide avec une erreur sur le bon champ', function () {
    designSave($this, [
        ['type' => 'texte', 'data' => ['content' => 'x', 'design' => ['align' => 'diagonal']]],
    ])->assertStatus(422)->assertJsonValidationErrors(['blocks.0.data.design.align']);
});

it('enregistre data.design et le remplace en bloc a la sauvegarde suivante', function () {
    designSave($this, [
        ['type' => 'texte', 'data' => ['content' => 'x', 'design' => ['align' => 'right']]],
    ])->assertOk();

    $block = $this->page->blocks()->first();
    expect($block->data['design'])->toBe(['align' => 'right']);

    designSave($this, [
        ['id' => $block->id, 'type' => 'texte', 'data' => ['content' => 'y']],
    ])->assertOk();

    expect($block->fresh()->data)->not->toHaveKey('design');
});
