<?php

use App\Models\Page;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
});

it('cree un separateur avec un style valide', function () {
    $page = Page::factory()->create();

    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'separateur', 'style' => 'fin']
    );

    $block = $page->blocks()->whereNull('parent_id')->first();
    expect($block->data['style'])->toBe('fin');
});

it('accepte un separateur sans style', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'separateur']
    );

    $response->assertSessionHasNoErrors();
});

it('rejette un style de separateur invalide', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'separateur', 'style' => 'inexistant']
    );

    $response->assertSessionHasErrors('style');
});
