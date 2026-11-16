<?php

use App\Services\Theme\ColorConverter;
use App\Services\Theme\ContrastChecker;
use App\Services\Theme\ThemeRoles;
use App\Services\Theme\ThemeStyleBuilder;

// Lot 3f-1 : couleurs d'etat (erreur). Un message d'erreur reste rouge quel que soit le theme ou la
// couleur primaire choisie ; la valeur vient d'une seule source (ThemeRoles) et ne passe pas par theme_colors.

function themeStatusRead(string $relative): string
{
    return (string) file_get_contents(dirname(__DIR__, 2).'/'.$relative);
}

function themeStatusTriplet(string $hex): string
{
    return implode(' ', array_map('hexdec', str_split(substr($hex, 1), 2)));
}

it('declare les trois couleurs d erreur dans ThemeRoles', function () {
    expect(ThemeRoles::statusDefaults())->toBe([
        'danger' => '#B91C1C',
        'danger-soft' => '#FEE2E2',
        'danger-content' => '#991B1B',
    ])
        ->and(ThemeRoles::statusVariable('danger-soft'))->toBe('--color-danger-soft-rgb');
});

it('refuse une couleur d etat inconnue', function () {
    expect(fn () => ThemeRoles::statusVariable('succes'))->toThrow(InvalidArgumentException::class);
});

it('declare dans shared.css les memes defauts que ThemeRoles', function () {
    $css = themeStatusRead('resources/css/shared.css');

    foreach (ThemeRoles::statusDefaults() as $name => $hex) {
        expect($css)->toContain(ThemeRoles::statusVariable($name).': '.themeStatusTriplet($hex).';');
    }
});

it('branche chaque couleur d erreur sur sa variable css dans les jetons', function () {
    $tokens = themeStatusRead('tailwind.tokens.js');

    foreach (array_keys(ThemeRoles::statusDefaults()) as $name) {
        expect($tokens)->toContain("'".ThemeRoles::statusVariable($name)."'");
    }
});

it('garde l erreur lisible sur chaque fond de theme et sur son fond doux', function () {
    $checker = new ContrastChecker;
    $status = ThemeRoles::statusDefaults();
    $backgrounds = [ThemeRoles::defaults()['bg'], ThemeRoles::defaults()['surface']];

    foreach (config('themes.themes') as $theme) {
        foreach (['bg', 'surface'] as $role) {
            if (isset($theme['palette'][$role])) {
                $backgrounds[] = $theme['palette'][$role];
            }
        }
    }

    expect(count($backgrounds))->toBeGreaterThan(2);

    foreach ($backgrounds as $background) {
        expect($checker->ratio($status['danger'], $background))->toBeGreaterThanOrEqual(4.5, "erreur sur {$background}");
    }

    expect($checker->ratio($status['danger-content'], $status['danger-soft']))->toBeGreaterThanOrEqual(4.5)
        ->and($checker->ratio($status['danger'], $status['danger-soft']))->toBeGreaterThanOrEqual(4.5);
});

it('ne laisse pas le theme ni les couleurs personnalisees changer le rouge d erreur', function () {
    $css = (new ThemeStyleBuilder(new ColorConverter))->css('#123ABC', '#000000', null, ['primary' => '#00AA00']);

    expect($css)->not->toContain('--color-danger');
});
