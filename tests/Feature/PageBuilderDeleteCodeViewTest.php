<?php

use App\Models\Page;

beforeEach(function () {
    $this->page = Page::factory()->create();
    $this->flag = fn ($user) => $this->actingAs($user)
        ->get(route('admin.pages.builder.show', $this->page))
        ->assertOk()
        ->viewData('builder')['requiresDeleteCode'];
});

it('demande le code de suppression a un utilisateur Publication', function () {
    expect(($this->flag)(publicationUser()))->toBeTrue();
});

it('ne demande pas le code au Super Admin', function () {
    expect(($this->flag)(adminUser()))->toBeFalse();
});
