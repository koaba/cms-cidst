<?php

use App\Models\Page;

it('donne draft a une page non publiee', function () {
    $page = Page::factory()->create(['is_published' => false]);

    expect($page->status)->toBe('draft');
});

it('donne draft a une page non publiee meme avec une date future', function () {
    $page = Page::factory()->create([
        'is_published' => false,
        'published_at' => now()->addDays(3),
    ]);

    expect($page->status)->toBe('draft');
});

it('donne scheduled a une page publiee dans le futur', function () {
    $page = Page::factory()->create([
        'is_published' => true,
        'published_at' => now()->addDays(3),
    ]);

    expect($page->status)->toBe('scheduled');
});

it('donne published a une page publiee sans date', function () {
    $page = Page::factory()->create([
        'is_published' => true,
        'published_at' => null,
    ]);

    expect($page->status)->toBe('published');
});

it('donne published a une page publiee dont la date est passee', function () {
    $page = Page::factory()->create([
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    expect($page->status)->toBe('published');
});
