<?php

use App\Models\Page;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
});

it('cree un bloc bouton avec label et url', function () {
    $page = Page::factory()->create();

    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'bouton', 'label' => 'Cliquez', 'url' => '/contact', 'style' => 'primaire']
    );

    $block = $page->blocks()->whereNull('parent_id')->first();
    expect($block->data['label'])->toBe('Cliquez');
    expect($block->data['url'])->toBe('/contact');
    expect($block->data['style'])->toBe('primaire');
});

it('rejette un bouton sans label', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'bouton', 'url' => '/contact']
    );

    $response->assertSessionHasErrors('label');
});

it('rejette un bouton sans url', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'bouton', 'label' => 'Cliquez']
    );

    $response->assertSessionHasErrors('url');
});

it('rejette un style de bouton invalide', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'bouton', 'label' => 'Cliquez', 'url' => '/contact', 'style' => 'inexistant']
    );

    $response->assertSessionHasErrors('style');
});

it('CARACTERISATION: new_tab est valide mais pas caste en booleen (meme piege que autoplay avant son fix)', function () {
    $page = Page::factory()->create();

    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'bouton', 'label' => 'Cliquez', 'url' => '/contact', 'new_tab' => '1']
    );

    $block = $page->blocks()->whereNull('parent_id')->first();

    // Documente le comportement ACTUEL : stocke la chaine "1", pas true.
    // Si ce test echoue apres un futur fix, mettre a jour l'assertion
    // ET verifier que le rendu Blade cote public gere bien le nouveau type.
    expect($block->data['new_tab'])->toBe('1');
    expect($block->data['new_tab'])->not->toBeBool();
});