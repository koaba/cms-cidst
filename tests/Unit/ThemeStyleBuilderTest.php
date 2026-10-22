<?php

use App\Services\Theme\ColorConverter;
use App\Services\Theme\ThemeStyleBuilder;

function themeCssOrEmpty(?string $primary, ?string $secondary): string
{
    try {
        $css = (new ThemeStyleBuilder(new ColorConverter))->css($primary, $secondary);

        return is_string($css) ? $css : '';
    } catch (Throwable) {
        return '';
    }
}

it('produit les variables DaisyUI et Tailwind du primary', function () {
    $css = themeCssOrEmpty('#C41E2A', '#17181A');

    expect($css)->toContain('--p:52.8615% 0.197918 24.594198;')
        ->and($css)->toContain('--pc:90.5723% 0.039584 24.594198;')
        ->and($css)->toContain('--a:52.8615% 0.197918 24.594198;')
        ->and($css)->toContain('--color-primary-rgb:196 30 42;');
});

it('produit les variables DaisyUI et Tailwind du secondary', function () {
    $css = themeCssOrEmpty('#C41E2A', '#17181A');

    expect($css)->toContain('--color-secondary-rgb:23 24 26;')
        ->and($css)->toMatch('/--s:[\d.]+% [\d.]+ [\d.]+;/')
        ->and($css)->toMatch('/--sc:[\d.]+% [\d.]+ [\d.]+;/')
        ->and($css)->toMatch('/--n:[\d.]+% [\d.]+ [\d.]+;/')
        ->and($css)->toMatch('/--nc:[\d.]+% [\d.]+ [\d.]+;/');
});

it('place le bloc sur html:root pour passer devant le CSS de DaisyUI', function () {
    expect(themeCssOrEmpty('#C41E2A', '#17181A'))->toStartWith('html:root{');
});

it('accepte l hexadecimal en minuscules comme en majuscules', function () {
    $upper = themeCssOrEmpty('#C41E2A', '#17181A');

    expect($upper)->not->toBe('')
        ->and(themeCssOrEmpty('#c41e2a', '#17181a'))->toBe($upper);
});

it('applique une couleur personnalisee sans toucher a l autre', function () {
    $css = themeCssOrEmpty('#123ABC', '#17181A');

    expect($css)->toContain('--color-primary-rgb:18 58 188;')
        ->and($css)->toContain('--color-secondary-rgb:23 24 26;');
});

it('retombe sur les defauts du theme pour une valeur invalide ou absente', function () {
    $defaults = themeCssOrEmpty('#C41E2A', '#17181A');
    $css = themeCssOrEmpty('javascript:alert(1)', null);

    expect($defaults)->toContain('--color-primary-rgb:196 30 42;')
        ->and($css)->toBe($defaults)
        ->and($css)->not->toContain('javascript');
});
