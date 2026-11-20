<?php

use App\Services\Theme\ColorConverter;
use App\Services\Theme\ContrastChecker;
use App\Services\Theme\ThemeRoles;
use App\Services\Theme\ThemeStyleBuilder;

// Lot 3e-1 : une palette de theme peut surcharger les couleurs d'etat (danger, success, warning,
// chacune en base, _soft et _content). Sans surcharge, rien n'est emis et shared.css garde la main.
// Les couleurs personnalisees (theme_colors) ne touchent jamais aux etats.

const SPA_FAMILIES = ['danger', 'success', 'warning'];

function spaCss(array $palette, ?array $custom = null): string
{
    config()->set('themes.themes.spa_fake', ['label' => 'Fake', 'palette' => $palette]);

    return (new ThemeStyleBuilder(new ColorConverter))->css(null, null, 'spa_fake', $custom);
}

/** @return list<string> Les controles de contraste qui echouent pour cette palette (valeurs par defaut si absentes). */
function spaViolations(array $palette): array
{
    $checker = new ContrastChecker;
    $defaults = ThemeRoles::statusDefaults();
    $bg = $palette['bg'] ?? ThemeRoles::defaults()['bg'];
    $surface = $palette['surface'] ?? ThemeRoles::defaults()['surface'];
    $violations = [];

    foreach (SPA_FAMILIES as $family) {
        $base = $palette[$family] ?? $defaults[$family];
        $soft = $palette[$family.'_soft'] ?? $defaults[$family.'-soft'];
        $content = $palette[$family.'_content'] ?? $defaults[$family.'-content'];

        foreach ([['fond', $bg], ['surface', $surface], ['doux', $soft]] as [$label, $background]) {
            if ($checker->ratio($base, $background) < 4.5) {
                $violations[] = "{$family} sur {$label}";
            }
        }

        if ($checker->ratio($content, $soft) < 4.5) {
            $violations[] = "{$family}_content sur {$family}_soft";
        }
    }

    return $violations;
}

it('n emet aucune couleur d etat quand la palette n en surcharge pas', function () {
    $css = spaCss(['primary' => '#9E1B32', 'bg' => '#F7F1F1']);

    foreach (SPA_FAMILIES as $family) {
        expect($css)->not->toContain('--color-'.$family);
    }
});

it('emet en triplets RGB les couleurs d etat surchargees par la palette', function () {
    $css = spaCss([
        'danger' => '#FCA5A5',
        'danger_soft' => '#450A0A',
        'danger_content' => '#FECACA',
        'success' => '#86EFAC',
    ]);

    expect($css)->toContain('--color-danger-rgb:252 165 165;')
        ->and($css)->toContain('--color-danger-soft-rgb:69 10 10;')
        ->and($css)->toContain('--color-danger-content-rgb:254 202 202;')
        ->and($css)->toContain('--color-success-rgb:134 239 172;')
        ->and($css)->not->toContain('--color-success-soft')
        ->and($css)->not->toContain('--color-warning');
});

it('ignore une couleur d etat invalide', function () {
    $css = spaCss(['danger' => 'rouge', 'danger_soft' => '#FFF']);

    expect($css)->not->toContain('--color-danger');
});

it('ne laisse pas les couleurs personnalisees changer une couleur d etat', function () {
    $css = spaCss(['danger' => '#FCA5A5'], ['danger' => '#00FF00', 'success' => '#00FF00']);

    expect($css)->toContain('--color-danger-rgb:252 165 165;')
        ->and($css)->not->toContain('0 255 0;--color-danger')
        ->and($css)->not->toContain('--color-success');
});

it('garde les etats lisibles dans chaque theme declare', function () {
    $themes = config('themes.themes');

    expect($themes)->not->toBeEmpty();

    foreach ($themes as $key => $theme) {
        expect(spaViolations($theme['palette'] ?? []))->toBe([], "theme {$key}");
    }
});

it('detecte un theme sombre qui garderait les etats clairs par defaut', function () {
    $violations = spaViolations(['bg' => '#0B1118', 'surface' => '#121A24']);

    expect($violations)->toContain('danger sur fond')
        ->and($violations)->toContain('danger sur surface')
        ->and($violations)->toContain('success sur fond');
});

it('accepte un theme sombre qui surcharge ses etats avec assez de contraste', function () {
    $palette = [
        'bg' => '#0B1118', 'surface' => '#121A24',
        'danger' => '#FCA5A5', 'danger_soft' => '#3B0D0D', 'danger_content' => '#FECACA',
        'success' => '#86EFAC', 'success_soft' => '#0B2E1A', 'success_content' => '#BBF7D0',
        'warning' => '#FCD34D', 'warning_soft' => '#33230A', 'warning_content' => '#FDE68A',
    ];

    expect(spaViolations($palette))->toBe([]);
});

it('refuse une couleur d etat entre 3 et 4,5 de contraste', function () {
    // #EF4444 sur blanc : environ 3,8, lisible pour un gros texte seulement.
    expect(spaViolations(['danger' => '#EF4444']))->toContain('danger sur surface')
        ->and(spaViolations(['danger' => '#B91C1C']))->toBe([]);
});
