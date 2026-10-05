<?php

use App\Models\Page;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
});

it('cree un bloc citation avec contenu et auteur', function () {
    $page = Page::factory()->create();

    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'citation', 'content' => 'Une phrase', 'author' => 'Un auteur']
    );

    $block = $page->blocks()->whereNull('parent_id')->first();
    expect($block->data['content'])->toBe('Une phrase');
    expect($block->data['author'])->toBe('Un auteur');
});

it('accepte une citation sans auteur', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'citation', 'content' => 'Sans auteur']
    );

    $response->assertSessionHasNoErrors();
});

it('rejette une citation sans contenu', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'citation', 'author' => 'Auteur seul']
    );

    $response->assertSessionHasErrors('content');
});
