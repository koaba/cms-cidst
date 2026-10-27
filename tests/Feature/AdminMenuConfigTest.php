<?php

use Illuminate\Support\Facades\Route;

it('lit le menu admin depuis config/admin_menu.php', function () {
    $items = config('admin_menu', []);
    expect($items)->not->toBeEmpty();
    foreach ($items as $item) {
        expect($item)->toHaveKeys(['label', 'route', 'active']);
        expect(Route::has($item['route']))->toBeTrue();
    }
});

it('montre toutes les entrees au Super Admin', function () {
    $items = config('admin_menu', []);
    expect($items)->not->toBeEmpty();
    $response = $this->actingAs(adminUser())->get('/admin')->assertOk();
    foreach ($items as $item) {
        $response->assertSee(route($item['route']), false);
    }
});

it('cache a Publication les entrees reservees au Super Admin', function () {
    $reserved = collect(config('admin_menu', []))->filter(fn ($item) => isset($item['roles']));
    expect($reserved)->not->toBeEmpty();
    $response = $this->actingAs(publicationUser())->get('/admin')->assertOk();
    foreach ($reserved as $item) {
        $response->assertDontSee(route($item['route']), false);
    }
});
