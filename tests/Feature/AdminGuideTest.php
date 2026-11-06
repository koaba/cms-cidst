<?php

it('redirige un visiteur non connecte', function () {
    $this->get('/admin/guide')->assertRedirect();
});

it('affiche le glossaire aux deux roles', function () {
    foreach ([adminUser(), publicationUser()] as $user) {
        $response = $this->actingAs($user)->get('/admin/guide')->assertOk();
        foreach (['slug', 'brouillon', 'programmee', 'publiee'] as $term) {
            $response->assertSee('data-term="'.$term.'"', false);
        }
    }
});

it('montre au Super Admin la description de tous les menus', function () {
    $response = $this->actingAs(adminUser())->get('/admin/guide')->assertOk()->assertSee('id="guide-menus"', false);
    foreach (config('admin_menu', []) as $item) {
        $response->assertSee($item['description']);
    }
});

it('cache a Publication les menus reserves', function () {
    $items = collect(config('admin_menu', []));
    expect($items->filter(fn ($i) => isset($i['roles']))->isNotEmpty())->toBeTrue();
    $response = $this->actingAs(publicationUser())->get('/admin/guide')->assertOk()->assertSee('id="guide-menus"', false);
    foreach ($items as $item) {
        isset($item['roles']) ? $response->assertDontSee($item['description']) : $response->assertSee($item['description']);
    }
});
