<?php

it('donne une description unique a chaque entree du menu admin', function () {
    $items = config('admin_menu', []);
    expect($items)->not->toBeEmpty();
    foreach ($items as $item) {
        expect($item['description'] ?? '')->toBeString()->not->toBeEmpty();
    }
    $descriptions = array_column($items, 'description');
    expect($descriptions)->toHaveCount(count(array_unique($descriptions)));
});

it('affiche tous les raccourcis au Super Admin', function () {
    $response = $this->actingAs(adminUser())->get('/admin')->assertOk()->assertSee('id="raccourcis"', false);
    foreach (config('admin_menu', []) as $item) {
        $response->assertSee($item['description']);
    }
});

it('cache a Publication les raccourcis reserves', function () {
    $items = collect(config('admin_menu', []));
    expect($items->filter(fn ($i) => isset($i['roles']))->isNotEmpty())->toBeTrue();
    $response = $this->actingAs(publicationUser())->get('/admin')->assertOk()->assertSee('id="raccourcis"', false);
    foreach ($items as $item) {
        isset($item['roles']) ? $response->assertDontSee($item['description']) : $response->assertSee($item['description']);
    }
});
