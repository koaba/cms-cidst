<?php

use App\Models\Page;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
});

it('cree un bloc section_fond avec texte et couleur', function () {
    $page = Page::factory()->create();

    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'section_fond', 'text' => 'Contenu de la section', 'bg_color' => 'blue']
    );

    $block = $page->blocks()->whereNull('parent_id')->first();
    expect($block->data['text'])->toBe('Contenu de la section');
    expect($block->data['bg_color'])->toBe('blue');
});

it('rejette section_fond sans texte', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'section_fond', 'title' => 'Titre seul']
    );

    $response->assertSessionHasErrors('text');
});

it('rejette une bg_color invalide', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'section_fond', 'text' => 'Contenu', 'bg_color' => 'rouge']
    );

    $response->assertSessionHasErrors('bg_color');
});

it('CARACTERISATION: button_new_tab est valide mais pas caste en booleen', function () {
    $page = Page::factory()->create();

    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        [
            'type' => 'section_fond',
            'text' => 'Contenu',
            'button_new_tab' => '1',
        ]
    );

    $block = $page->blocks()->whereNull('parent_id')->first();
    expect($block->data['button_new_tab'])->toBe('1');
    expect($block->data['button_new_tab'])->not->toBeBool();
});