<?php

use App\Models\SiteSetting;

// Lot 3d-1 : les variables des roles arrivent dans les deux layouts (public et admin).

it('injecte les variables des roles sur la page publique', function () {
    $html = $this->get('/')->getContent();

    expect($html)->toContain('--color-bg-rgb:241 242 244;')
        ->and($html)->toContain('--color-surface-rgb:255 255 255;')
        ->and($html)->toContain('--color-text-rgb:23 24 26;')
        ->and($html)->toContain('--color-muted-rgb:75 80 87;')
        ->and($html)->toContain('--color-border-rgb:216 218 222;')
        ->and($html)->toContain('--color-primary-content-rgb:255 255 255;');
});

it('injecte les variables des roles dans l administration', function () {
    $html = $this->actingAs(adminUser())->get(route('admin.dashboard'))->assertOk()->getContent();

    expect($html)->toContain('--color-bg-rgb:241 242 244;')
        ->and($html)->toContain('--color-primary-content-rgb:255 255 255;');
});

it('adapte le texte sur primaire a la couleur choisie dans les reglages', function () {
    SiteSetting::current()
        ->forceFill(['primary_color' => '#FFE000'])
        ->saveQuietly();

    expect($this->get('/')->getContent())->toContain('--color-primary-content-rgb:0 0 0;');
});
