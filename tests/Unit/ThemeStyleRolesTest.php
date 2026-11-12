<?php

use App\Services\Theme\ColorConverter;
use App\Services\Theme\ThemeStyleBuilder;

// Lot 3d-1 : le bloc CSS du theme emet aussi les cinq autres roles et le texte sur primaire,
// sans rien changer aux variables existantes.

function themeRolesCss(?string $primary = '#C41E2A', ?string $secondary = '#17181A'): string
{
    return (new ThemeStyleBuilder(new ColorConverter))->css($primary, $secondary);
}

it('ne change pas les variables existantes du theme', function () {
    $legacy = 'html:root{--p:52.8615% 0.197918 24.594198;--pc:90.5723% 0.039584 24.594198;'
        .'--a:52.8615% 0.197918 24.594198;--ac:90.5723% 0.039584 24.594198;'
        .'--s:20.8789% 0.004235 264.476575;--sc:84.1758% 0.000847 264.476575;'
        .'--n:20.8789% 0.004235 264.476575;--nc:84.1758% 0.000847 264.476575;'
        .'--color-primary-rgb:196 30 42;--color-secondary-rgb:23 24 26;';

    expect(themeRolesCss())->toStartWith($legacy);
});

it('emet les cinq autres roles avec leurs valeurs par defaut', function () {
    $css = themeRolesCss();

    expect($css)->toContain('--color-bg-rgb:241 242 244;')
        ->and($css)->toContain('--color-surface-rgb:255 255 255;')
        ->and($css)->toContain('--color-text-rgb:23 24 26;')
        ->and($css)->toContain('--color-muted-rgb:75 80 87;')
        ->and($css)->toContain('--color-border-rgb:216 218 222;');
});

it('calcule le texte sur primaire en blanc pour le rouge du site', function () {
    expect(themeRolesCss())->toContain('--color-primary-content-rgb:255 255 255;');
});

it('calcule le texte sur primaire en noir pour une primaire claire', function () {
    expect(themeRolesCss('#FFE000'))->toContain('--color-primary-content-rgb:0 0 0;');
});

it('retombe sur le texte du primaire par defaut quand la primaire est invalide', function () {
    $css = themeRolesCss('red;}a{');

    expect($css)->toContain('--color-primary-content-rgb:255 255 255;')
        ->and($css)->not->toContain('red;}a{');
});

it('termine le bloc proprement', function () {
    expect(themeRolesCss())->toEndWith(';}');
});
