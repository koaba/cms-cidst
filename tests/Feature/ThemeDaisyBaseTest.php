<?php

use App\Services\Theme\ColorConverter;
use App\Services\Theme\ThemeStyleBuilder;

// Lot 3e-0 : les variables de base de DaisyUI (base-100, base-200, base-300, base-content) suivent
// les roles surface, bg, border et text. Prerequis des themes Clair et Sombre : sans cela,
// bg-base-100 resterait blanc quel que soit le theme.

function daisyBaseBuilder(): ThemeStyleBuilder
{
    return new ThemeStyleBuilder(new ColorConverter);
}

function daisyBaseVar(string $css, string $name): ?string
{
    return preg_match('/'.preg_quote($name, '/').':([^;]+);/', $css, $m) === 1 ? $m[1] : null;
}

it('emet les quatre variables de base DaisyUI', function () {
    $css = daisyBaseBuilder()->css('#C41E2A', '#17181A');

    foreach (['--b1', '--b2', '--b3', '--bc'] as $name) {
        expect(daisyBaseVar($css, $name))->not->toBeNull("{$name} absente");
    }
});

it('lie base-100 a la surface, base-200 au fond, base-300 a la bordure et base-content au texte', function () {
    $converter = new ColorConverter;
    $css = daisyBaseBuilder()->css('#C41E2A', '#17181A', null, [
        'surface' => '#101820',
        'bg' => '#0A0F14',
        'border' => '#2A3642',
        'text' => '#E8EDF2',
    ]);

    expect(daisyBaseVar($css, '--b1'))->toBe($converter->daisyOklch('#101820'))
        ->and(daisyBaseVar($css, '--b2'))->toBe($converter->daisyOklch('#0A0F14'))
        ->and(daisyBaseVar($css, '--b3'))->toBe($converter->daisyOklch('#2A3642'))
        ->and(daisyBaseVar($css, '--bc'))->toBe($converter->daisyOklch('#E8EDF2'));
});

it('suit la palette d un theme', function () {
    $converter = new ColorConverter;
    $css = daisyBaseBuilder()->css(null, null, 'bordeaux');

    expect(daisyBaseVar($css, '--b2'))->toBe($converter->daisyOklch('#F7F1F1'))
        ->and(daisyBaseVar($css, '--bc'))->toBe($converter->daisyOklch('#241518'));
});

it('garde le rendu du theme par defaut : les valeurs DaisyUI de tailwind.config.js', function () {
    $converter = new ColorConverter;
    $css = daisyBaseBuilder()->css('#C41E2A', '#17181A');

    expect(daisyBaseVar($css, '--b1'))->toBe($converter->daisyOklch('#FFFFFF'))
        ->and(daisyBaseVar($css, '--b2'))->toBe($converter->daisyOklch('#F1F2F4'))
        ->and(daisyBaseVar($css, '--b3'))->toBe($converter->daisyOklch('#D8DADE'));
});
