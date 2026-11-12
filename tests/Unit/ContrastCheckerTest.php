<?php

use App\Services\Theme\ContrastChecker;

// Lot 3d-1 : un seul calcul de contraste WCAG pour le theme, le garde-fou des reglages et les tests.

it('mesure le contraste maximal entre le noir et le blanc', function () {
    expect((new ContrastChecker)->ratio('#000000', '#FFFFFF'))->toEqualWithDelta(21.0, 0.001);
});

it('mesure un contraste de 1 entre deux couleurs identiques', function () {
    expect((new ContrastChecker)->ratio('#C41E2A', '#C41E2A'))->toEqualWithDelta(1.0, 0.001);
});

it('retrouve les contrastes mesures pour la conception des themes', function (string $a, string $b, float $expected) {
    expect((new ContrastChecker)->ratio($a, $b))->toEqualWithDelta($expected, 0.01);
})->with([
    'texte secondaire actuel sur le fond' => ['#6B7078', '#F1F2F4', 4.45],
    'texte secondaire actuel sur blanc' => ['#6B7078', '#FFFFFF', 4.98],
    'texte secondaire retenu sur le fond' => ['#4B5057', '#F1F2F4', 7.25],
    'texte secondaire retenu sur blanc' => ['#4B5057', '#FFFFFF', 8.13],
    'blanc sur le primaire par defaut' => ['#FFFFFF', '#C41E2A', 5.89],
    'texte secondaire du theme sombre sur noir' => ['#B3B3B3', '#000000', 10.02],
    'rouge sombre en texte sur noir' => ['#DA0000', '#000000', 3.98],
]);

it('donne le meme contraste dans les deux sens et quelle que soit la casse', function () {
    $checker = new ContrastChecker;

    expect($checker->ratio('#6b7078', '#f1f2f4'))->toBe($checker->ratio('#F1F2F4', '#6B7078'));
});

it('compare le contraste a un seuil de 4,5 par defaut', function () {
    $checker = new ContrastChecker;

    expect($checker->passes('#6B7078', '#F1F2F4'))->toBeFalse()
        ->and($checker->passes('#6B7078', '#F1F2F4', 4.4))->toBeTrue()
        ->and($checker->passes('#4B5057', '#F1F2F4'))->toBeTrue();
});

it('choisit le blanc ou le noir selon le meilleur contraste', function (string $background, string $expected) {
    expect((new ContrastChecker)->readableTextOn($background))->toBe($expected);
})->with([
    'rouge du site' => ['#C41E2A', '#FFFFFF'],
    'rouge du theme sombre' => ['#DA0000', '#FFFFFF'],
    'bleu fonce' => ['#17181A', '#FFFFFF'],
    'noir' => ['#000000', '#FFFFFF'],
    'blanc' => ['#FFFFFF', '#000000'],
    'jaune clair' => ['#FFE000', '#000000'],
]);

it('refuse une couleur qui n est pas du #RRGGBB', function (string $invalid) {
    expect(fn () => (new ContrastChecker)->ratio($invalid, '#FFFFFF'))->toThrow(InvalidArgumentException::class);
})->with(['rouge', '#FFF', '#GGGGGG', '', 'red;}a{']);
