<?php

use App\Models\Page;

it('affiche un champ datetime-local a la creation', function () {
    $this->actingAs(adminUser())
        ->get(route('admin.pages.create'))
        ->assertOk()
        ->assertSee('type="datetime-local" name="published_at"', false);
});

it('prerempli la date et l heure a l edition', function () {
    $page = Page::factory()->create([
        'is_published' => true,
        'published_at' => '2026-10-22 14:30:00',
    ]);

    $this->actingAs(adminUser())
        ->get(route('admin.pages.edit', $page))
        ->assertOk()
        ->assertSee('value="2026-10-22T14:30"', false);
});

it('enregistre l heure envoyee par le formulaire', function () {
    $page = Page::factory()->create(['is_published' => true]);

    $this->actingAs(adminUser())
        ->put(route('admin.pages.update', $page), [
            'title' => 'Titre',
            'content' => 'Contenu',
            'published_at' => '2026-10-22T14:30',
            'is_published' => '1',
        ])
        ->assertRedirect(route('admin.pages.index'));

    expect($page->fresh()->published_at->format('Y-m-d H:i'))
        ->toBe('2026-10-22 14:30');
});
