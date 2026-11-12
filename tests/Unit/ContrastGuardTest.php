<?php

use App\Services\Theme\ContrastChecker;
use App\Services\Theme\ContrastGuard;
use App\Services\Theme\ThemePalette;

// Lot 3d-4b : garde-fou de contraste (politique A). Le texte principal sur le fond et sur la
// surface BLOQUE sous 4,5 ; texte secondaire, couleur primaire et bordures n'AVERTISSENT que.
// Le ratio vient du seul ContrastChecker.

function contrastGuardPalette(array $colors = []): ThemePalette
{
    return ThemePalette::resolve($colors);
}

it('ne signale rien sur les couleurs par defaut', function () {
    $guard = new ContrastGuard;

    expect($guard->blockers(contrastGuardPalette()))->toBe([])
        ->and($guard->warnings(contrastGuardPalette()))->toBe([]);
});

it('decrit six paires avec leur ratio et leur seuil', function () {
    $report = (new ContrastGuard)->report(contrastGuardPalette());

    expect($report)->toHaveCount(6)
        ->and(array_column($report, 'label'))->toBe([
            'Texte principal sur le fond',
            'Texte principal sur la surface',
            'Texte secondaire sur le fond',
            'Texte secondaire sur la surface',
            'Couleur primaire sur la surface',
            'Bordures sur la surface',
        ])
        ->and(array_column($report, 'blocking'))->toBe([true, true, false, false, false, false])
        ->and(array_column($report, 'minimum'))->toBe([4.5, 4.5, 4.5, 4.5, 3.0, 1.2])
        ->and(array_column($report, 'passes'))->each->toBeTrue();
});

it('reprend le ratio du ContrastChecker', function () {
    $report = (new ContrastGuard)->report(contrastGuardPalette(['text' => '#336699', 'bg' => '#FFEEDD']));
    $expected = (new ContrastChecker)->ratio('#336699', '#FFEEDD');

    expect($report[0]['ratio'])->toBe($expected);
});

it('bloque le texte principal sous 4,5 sur le fond', function () {
    $blockers = (new ContrastGuard)->blockers(contrastGuardPalette(['text' => '#CCCCCC']));

    // Gris clair : illisible sur le fond ET sur la surface blanche.
    expect($blockers)->toHaveCount(2)
        ->and($blockers[0])->toStartWith('Texte principal sur le fond')
        ->and($blockers[1])->toStartWith('Texte principal sur la surface');
});

it('bloque le texte principal sous 4,5 sur la surface', function () {
    $blockers = (new ContrastGuard)->blockers(contrastGuardPalette(['bg' => '#000000', 'text' => '#FFFFFF', 'surface' => '#EEEEEE']));

    expect($blockers)->toHaveCount(1)->and($blockers[0])->toStartWith('Texte principal sur la surface');
});

it('accepte un texte a 4,5 et bloque juste en dessous', function () {
    // #767676 sur blanc fait 4,54 ; #777777 sur blanc fait 4,47.
    $white = ['bg' => '#FFFFFF', 'surface' => '#FFFFFF'];

    expect((new ContrastGuard)->blockers(contrastGuardPalette([...$white, 'text' => '#767676'])))->toBe([])
        ->and((new ContrastGuard)->blockers(contrastGuardPalette([...$white, 'text' => '#777777'])))->toHaveCount(2);
});

it('ne fait qu avertir pour les autres roles', function () {
    $palette = contrastGuardPalette(['muted' => '#BBBBBB', 'primary' => '#EEEEEE', 'border' => '#FFFFFF']);
    $guard = new ContrastGuard;

    expect($guard->blockers($palette))->toBe([])
        ->and($guard->warnings($palette))->toHaveCount(4);
});

it('formate le message avec le contraste et le minimum', function () {
    $messages = (new ContrastGuard)->blockers(contrastGuardPalette(['text' => '#CCCCCC']));

    expect($messages[0])->toBe('Texte principal sur le fond : contraste 1,43:1, minimum 4,5:1.');
});

it('formate un ratio sans arrondir vers le haut', function () {
    expect(ContrastGuard::format(4.4999))->toBe('4,49')
        ->and(ContrastGuard::format(4.5))->toBe('4,5')
        ->and(ContrastGuard::format(3.0))->toBe('3')
        ->and(ContrastGuard::format(21.0))->toBe('21')
        ->and(ContrastGuard::format(1.2))->toBe('1,2');
});
