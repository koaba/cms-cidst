<?php

use App\Models\Page;

it('rend la classe d alignement choisie', function (string $align, string $class) {
    $page = Page::factory()->create();
    $page->blocks()->create([
        'type' => 'texte',
        'data' => ['content' => 'bonjour', 'design' => ['align' => $align]],
        'order' => 0,
    ]);

    $this->get(route('pages.show', $page))
        ->assertOk()
        ->assertSee($class, false);
})->with([
    ['left', 'text-left'],
    ['center', 'text-center'],
    ['right', 'text-right'],
]);

it('ne rend aucune classe d alignement sans design', function () {
    $page = Page::factory()->create();
    $page->blocks()->create([
        'type' => 'texte',
        'data' => ['content' => 'bonjour'],
        'order' => 0,
    ]);

    $this->get(route('pages.show', $page))
        ->assertOk()
        ->assertDontSee('text-center', false)
        ->assertDontSee('text-right', false);
});
