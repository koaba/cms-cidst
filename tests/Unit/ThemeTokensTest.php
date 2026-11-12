<?php

use App\Services\Theme\ThemeRoles;

// Lot 3d-1 : un seul fichier de jetons Tailwind pour l'admin et le public, branche sur les
// variables CSS des roles. Ces tests lisent les fichiers : ils echouent si les trois
// endroits (ThemeRoles, shared.css, tailwind.tokens.js) se desynchronisent.

function themeTokensRead(string $relative): string
{
    return (string) file_get_contents(dirname(__DIR__, 2).'/'.$relative);
}

it('importe les jetons partages dans les deux builds Tailwind', function (string $config) {
    $source = themeTokensRead($config);

    expect($source)->toContain("from './tailwind.tokens.js'")
        ->and($source)->toContain('cidstColors')
        ->and($source)->toContain('cidstFontFamily');
})->with(['tailwind.config.js', 'tailwind.public.config.js']);

it('ne redefinit aucune couleur cidst en dur dans la config publique', function () {
    expect(themeTokensRead('tailwind.public.config.js'))->not->toMatch('/#[0-9A-Fa-f]{6}\b/');
});

it('branche chaque role sur sa variable css dans les jetons', function () {
    $tokens = themeTokensRead('tailwind.tokens.js');

    foreach (ThemeRoles::keys() as $role) {
        expect($tokens)->toContain("'".ThemeRoles::cssVariable($role)."'");
    }

    expect($tokens)->toContain("'".ThemeRoles::PRIMARY_CONTENT_VARIABLE."'");
});

it('ne reference dans les jetons que des variables de role connues', function () {
    preg_match_all('/--color-[a-z-]+-rgb/', themeTokensRead('tailwind.tokens.js'), $matches);

    $known = [...array_map(fn (string $role) => ThemeRoles::cssVariable($role), ThemeRoles::keys()), ThemeRoles::PRIMARY_CONTENT_VARIABLE];

    expect(array_values(array_unique($matches[0])))->not->toBe([])
        ->and(array_diff($matches[0], $known))->toBe([]);
});

it('declare dans shared.css les memes defauts que ThemeRoles', function () {
    $css = themeTokensRead('resources/css/shared.css');

    foreach (ThemeRoles::defaults() as $role => $hex) {
        [$r, $g, $b] = sscanf(substr($hex, 1), '%2x%2x%2x');

        expect($css)->toContain(ThemeRoles::cssVariable($role).': '."{$r} {$g} {$b};");
    }

    expect($css)->toContain(ThemeRoles::PRIMARY_CONTENT_VARIABLE.': 255 255 255;');
});
