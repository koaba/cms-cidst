<?php

use App\Models\Page;

it('emet un JSON-LD WebPage sur une page publique', function () {
    $page = Page::factory()->create([
        'title' => 'Ma page JSON-LD',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $this->get(route('pages.show', $page))
        ->assertOk()
        ->assertSee('"@type":"WebPage"', false)
        ->assertSee('"name":"Ma page JSON-LD"', false);
});