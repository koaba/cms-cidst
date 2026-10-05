<?php

use App\Models\Page;

it('inclut og:locale fr_FR dans le head public', function () {
    $page = Page::factory()->create([
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $this->get(route('pages.show', $page))
        ->assertOk()
        ->assertSee('<meta property="og:locale" content="fr_FR">', false);
});
