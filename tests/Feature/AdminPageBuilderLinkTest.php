<?php

use App\Models\Page;

it('pointe vers le builder depuis la liste des pages', function () {
    $page = Page::factory()->create();

    $this->actingAs(adminUser())
        ->get(route('admin.pages.index'))
        ->assertOk()
        ->assertSee(route('admin.pages.builder.show', $page), false);
});
