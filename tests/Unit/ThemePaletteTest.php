<?php

use App\Services\Theme\ThemePalette;
use App\Services\Theme\ThemeRoles;
use App\Services\Theme\ThemeStyleBuilder;

// Lot 3d-1 : les sept roles de couleur et leur resolution (defaut, puis couches successives).

it('declare les sept roles de couleur dans l ordre', function () {
    expect(ThemeRoles::keys())->toBe(['primary', 'secondary', 'bg', 'surface', 'text', 'muted', 'border']);
});

it('donne un libelle et un defaut hexadecimal a chaque role', function () {
    foreach (ThemeRoles::keys() as $role) {
        expect(ThemeRoles::label($role))->not->toBe('')
            ->and(ThemeRoles::defaults()[$role])->toMatch('/^#[0-9A-F]{6}$/');
    }
});

it('garde pour defauts les couleurs actuelles du site (aucun changement visuel)', function () {
    expect(ThemeRoles::defaults())->toBe([
        'primary' => '#C41E2A',
        'secondary' => '#17181A',
        'bg' => '#F1F2F4',
        'surface' => '#FFFFFF',
        'text' => '#17181A',
        'muted' => '#4B5057',
        'border' => '#D8DADE',
    ]);
});

it('a pour defauts primaire et secondaire ceux du builder', function () {
    expect(ThemeRoles::defaults()['primary'])->toBe(ThemeStyleBuilder::DEFAULT_PRIMARY)
        ->and(ThemeRoles::defaults()['secondary'])->toBe(ThemeStyleBuilder::DEFAULT_SECONDARY);
});

it('derive le nom de la variable css de chaque role', function () {
    expect(ThemeRoles::cssVariable('primary'))->toBe('--color-primary-rgb')
        ->and(ThemeRoles::cssVariable('bg'))->toBe('--color-bg-rgb')
        ->and(ThemeRoles::PRIMARY_CONTENT_VARIABLE)->toBe('--color-primary-content-rgb');
});

it('refuse un role inconnu', function () {
    expect(fn () => ThemeRoles::label('accent'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => ThemeRoles::cssVariable('accent'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => ThemePalette::resolve()->color('accent'))->toThrow(InvalidArgumentException::class);
});

it('resout les defauts quand aucune couche n est donnee', function () {
    $palette = ThemePalette::resolve();

    expect($palette->all())->toBe(ThemeRoles::defaults())
        ->and($palette->color('bg'))->toBe('#F1F2F4');
});

it('applique une couche sur les defauts sans toucher aux autres roles', function () {
    $palette = ThemePalette::resolve(['primary' => '#123ABC']);

    expect($palette->color('primary'))->toBe('#123ABC')
        ->and($palette->color('secondary'))->toBe('#17181A')
        ->and($palette->color('surface'))->toBe('#FFFFFF');
});

it('laisse la derniere couche l emporter', function () {
    $palette = ThemePalette::resolve(
        ['primary' => '#111111', 'bg' => '#222222'],
        ['primary' => '#333333'],
    );

    expect($palette->color('primary'))->toBe('#333333')
        ->and($palette->color('bg'))->toBe('#222222');
});

it('ignore une valeur invalide et garde celle de la couche precedente', function () {
    $palette = ThemePalette::resolve(['primary' => '#123ABC'], ['primary' => 'red;}a{', 'bg' => '#FFF']);

    expect($palette->color('primary'))->toBe('#123ABC')
        ->and($palette->color('bg'))->toBe('#F1F2F4');
});

it('ignore les cles inconnues et les valeurs qui ne sont pas du texte', function () {
    $palette = ThemePalette::resolve(['accent' => '#FFAFBD', 'primary' => null, 'bg' => 123, 'text' => ['#000000']]);

    expect($palette->all())->toBe(ThemeRoles::defaults());
});

it('normalise les couleurs en majuscules', function () {
    expect(ThemePalette::resolve(['primary' => '#c41e2b'])->color('primary'))->toBe('#C41E2B');
});
