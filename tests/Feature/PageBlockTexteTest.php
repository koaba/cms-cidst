<?php

use App\Models\Page;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
});

it('cree un bloc texte avec titre et contenu', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'texte', 'title' => 'Mon titre', 'content' => 'Mon contenu']
    );

    $response->assertRedirect(route('admin.pages.blocks.index', $page));
    $block = $page->blocks()->whereNull('parent_id')->first();

    expect($block->type)->toBe('texte');
    expect($block->data['title'])->toBe('Mon titre');
    expect($block->data['content'])->toBe('Mon contenu');
});

it('accepte un bloc texte sans titre', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'texte', 'content' => 'Contenu seul']
    );

    $response->assertSessionHasNoErrors();
    expect($page->blocks()->whereNull('parent_id')->first()->data['title'] ?? null)->toBeNull();
});

it('rejette un bloc texte sans contenu', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'texte', 'title' => 'Titre seul']
    );

    $response->assertSessionHasErrors('content');
    expect($page->blocks()->whereNull('parent_id')->count())->toBe(0);
});

it('modifie un bloc texte existant', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'texte', 'content' => 'Avant']
    );
    $block = $page->blocks()->whereNull('parent_id')->first();

    $this->actingAs($this->admin)->put(
        route('admin.pages.blocks.update', [$page, $block->id]),
        ['content' => 'Apres']
    );

    expect($block->fresh()->data['content'])->toBe('Apres');
});