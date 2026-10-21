<?php

it('rend le layout et le sidebar admin avec les classes de theme, sans gris en dur', function () {
    $html = $this->actingAs(adminUser())->get(route('admin.pages.index'))->assertOk()->getContent();

    expect($html)
        ->toContain('bg-admin-bg')
        ->toContain('bg-admin-nav')
        ->toContain('text-admin-text')
        ->not->toContain('bg-gray-100')
        ->not->toContain('bg-gray-800')
        ->not->toContain('bg-gray-700')
        ->not->toContain('text-gray-300');
});

it('applique la classe active uniquement au lien de la section courante', function () {
    $html = $this->actingAs(adminUser())->get(route('admin.pages.index'))->assertOk()->getContent();

    expect(substr_count($html, 'bg-admin-hover text-white block'))->toBe(1);
});
