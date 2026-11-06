<?php

it('produit des balises nav ouvrantes et fermantes equilibrees dans l admin', function () {
    $html = $this->actingAs(adminUser())->get(route('admin.dashboard'))->assertOk()->getContent();

    expect(preg_match_all('/<nav\b/', $html))->toBe(preg_match_all('/<\/nav>/', $html));
});

it('donne un nom accessible a la navigation laterale', function () {
    $this->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('aria-label="Navigation principale"', false);
});
