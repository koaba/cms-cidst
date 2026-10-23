<?php

use App\Models\Page;

it('affiche le champ slug a la creation', function () {
    $this->actingAs(adminUser())
        ->get(route('admin.pages.create'))
        ->assertOk()
        ->assertSee('name="slug"', false);
});

it('affiche le champ slug prerempli a l edition', function () {
    $page = Page::factory()->create(['title' => 'Titre', 'slug' => 'mon-slug']);

    $this->actingAs(adminUser())
        ->get(route('admin.pages.edit', $page))
        ->assertOk()
        ->assertSee('name="slug"', false)
        ->assertSee('value="mon-slug"', false);
});
