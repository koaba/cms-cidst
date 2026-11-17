<?php

use App\Services\Theme\ColorConverter;
use App\Services\Theme\ContrastChecker;
use App\Services\Theme\ThemeRoles;
use App\Services\Theme\ThemeStyleBuilder;

// Lot 3f-1b : couleurs d'etat succes et alerte, fixes comme danger (hors theme et hors theme_colors).

function successWarningRead(string $relative): string
{
    return (string) file_get_contents(dirname(__DIR__, 2).'/'.$relative);
}

function successWarningTriplet(string $hex): string
{
    return implode(' ', array_map('hexdec', str_split(substr($hex, 1), 2)));
}

const SUCCESS_WARNING_TOKENS = [
    'success' => '#166534',
    'success-soft' => '#DCFCE7',
    'success-content' => '#14532D',
    'warning' => '#92400E',
    'warning-soft' => '#FEF3C7',
    'warning-content' => '#78350F',
];

it('declare les six couleurs succes et alerte dans ThemeRoles sans toucher a danger', function () {
    $status = ThemeRoles::statusDefaults();

    foreach (SUCCESS_WARNING_TOKENS as $name => $hex) {
        expect($status[$name] ?? null)->toBe($hex);
    }

    expect($status['danger'])->toBe('#B91C1C')
        ->and(ThemeRoles::statusVariable('warning-soft'))->toBe('--color-warning-soft-rgb');
});

it('declare dans shared.css et dans les jetons chaque couleur succes et alerte', function () {
    $css = successWarningRead('resources/css/shared.css');
    $tokens = successWarningRead('tailwind.tokens.js');

    foreach (SUCCESS_WARNING_TOKENS as $name => $hex) {
        expect($css)->toContain(ThemeRoles::statusVariable($name).': '.successWarningTriplet($hex).';')
            ->and($tokens)->toContain("'".ThemeRoles::statusVariable($name)."'");
    }
});

it('garde succes et alerte lisibles sur chaque fond de theme et sur leur fond doux', function () {
    $checker = new ContrastChecker;
    $backgrounds = [ThemeRoles::defaults()['bg'], ThemeRoles::defaults()['surface']];

    // Test unitaire : l'application n'est pas demarree, config() est donc indisponible.
    foreach ((require dirname(__DIR__, 2).'/config/themes.php')['themes'] as $theme) {
        foreach (['bg', 'surface'] as $role) {
            if (isset($theme['palette'][$role])) {
                $backgrounds[] = $theme['palette'][$role];
            }
        }
    }

    expect(count($backgrounds))->toBeGreaterThan(2);

    foreach (['success', 'warning'] as $kind) {
        foreach ($backgrounds as $background) {
            expect($checker->ratio(SUCCESS_WARNING_TOKENS[$kind], $background))
                ->toBeGreaterThanOrEqual(4.5, "{$kind} sur {$background}");
        }

        expect($checker->ratio(SUCCESS_WARNING_TOKENS[$kind.'-content'], SUCCESS_WARNING_TOKENS[$kind.'-soft']))->toBeGreaterThanOrEqual(4.5)
            ->and($checker->ratio(SUCCESS_WARNING_TOKENS[$kind], SUCCESS_WARNING_TOKENS[$kind.'-soft']))->toBeGreaterThanOrEqual(4.5);
    }
});

it('ne laisse pas le theme ni les couleurs personnalisees changer le vert et l orange d etat', function () {
    $css = (new ThemeStyleBuilder(new ColorConverter))->css('#123ABC', '#000000', null, ['primary' => '#00AA00']);

    expect($css)->not->toContain('--color-success')->not->toContain('--color-warning');
});
