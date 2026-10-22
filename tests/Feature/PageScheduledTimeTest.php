<?php

use App\Models\Page;

it('programme la publication a l heure pres', function (string $now, string $status) {
    $page = Page::factory()->create([
        'is_published' => true,
        'published_at' => '2026-10-22 14:30:00',
    ]);

    $this->travelTo($now);

    expect($page->fresh()->status)->toBe($status);
})->with([
    'avant' => ['2026-10-22 14:00:00', 'scheduled'],
    'apres' => ['2026-10-22 14:31:00', 'published'],
]);

it('masque la page publique avant l heure de publication', function () {
    $page = Page::factory()->create([
        'is_published' => true,
        'published_at' => '2026-10-22 14:30:00',
    ]);

    $this->travelTo('2026-10-22 14:00:00');

    $this->get("/pages/{$page->slug}")->assertNotFound();
});
