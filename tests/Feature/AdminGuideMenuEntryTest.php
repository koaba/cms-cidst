<?php

it('declare le Guide dans le menu admin pour tous les roles', function () {
    $item = collect(config('admin_menu'))->firstWhere('route', 'admin.guide');
    expect($item)->not->toBeNull();
    expect($item)->not->toHaveKey('roles');
    expect($item['description'] ?? '')->not->toBeEmpty();
});

it('affiche le Guide dans la sidebar de chaque role', function () {
    foreach ([adminUser(), publicationUser()] as $user) {
        $this->actingAs($user)->get('/admin')->assertOk()->assertSee(route('admin.guide'), false);
    }
});
