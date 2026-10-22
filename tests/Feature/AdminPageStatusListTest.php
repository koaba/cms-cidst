<?php

use App\Models\Page;

it('affiche le statut dans la liste admin', function (array $attrs, string $label) {
    Page::factory()->create($attrs);

    $response = $this->actingAs(adminUser())->get(route('admin.pages.index'));

    $response->assertOk();
    $response->assertSee($label);
})->with([
    'brouillon' => [['is_published' => false], 'Brouillon'],
    'programmee' => [
        ['is_published' => true, 'published_at' => now()->addDays(3)],
        "Programm\u{E9}e",
    ],
    'publiee' => [['is_published' => true, 'published_at' => null], "Publi\u{E9}e"],
]);
