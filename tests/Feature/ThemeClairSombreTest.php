<?php

use App\Services\Theme\ColorConverter;
use App\Services\Theme\ContrastChecker;
use App\Services\Theme\ThemeStyleBuilder;

// Lot 3e-2 : themes Clair et Sombre, et coque d'administration (sidebar, fond) pilotee par la palette
// (cles admin_bg, admin_nav, admin_hover, admin_text -> variables --admin-*).

const CS_ROLES = ['primary', 'secondary', 'accent', 'bg', 'surface', 'text', 'muted', 'border'];
const CS_ADMIN = ['admin_bg', 'admin_nav', 'admin_hover', 'admin_text'];
const CS_STATUS = ['danger', 'danger_soft', 'danger_content', 'success', 'success_soft', 'success_content', 'warning', 'warning_soft', 'warning_content'];

dataset('clair_sombre', ['clair', 'sombre']);

function csPalette(string $key): array
{
    $palette = config('themes.themes.'.$key.'.palette');

    expect($palette)->toBeArray("le theme {$key} est absent de config/themes.php");

    return $palette;
}

function csRatio(string $a, string $b): float
{
    return (new ContrastChecker)->ratio($a, $b);
}

function csLuminance(string $hex): float
{
    return (csRatio($hex, '#000000') - 1) / 20; // ratio avec le noir = (L + 0,05) / 0,05
}

it('declare le theme avec un libelle et tous ses roles', function (string $key) {
    $palette = csPalette($key);

    expect(config('themes.themes.'.$key.'.label'))->toBeString()->not->toBe('');

    foreach (array_merge(CS_ROLES, ['primary_content', 'secondary_content', 'accent_content'], CS_ADMIN) as $role) {
        expect($palette[$role] ?? null)->toMatch('/^#[0-9A-F]{6}$/', "{$key} : {$role} manquant ou invalide");
    }
})->with('clair_sombre');

it('garde le texte lisible sur le fond et la surface', function (string $key) {
    $palette = csPalette($key);

    foreach (['bg', 'surface'] as $background) {
        expect(csRatio($palette['text'], $palette[$background]))->toBeGreaterThanOrEqual(4.5, "{$key} : texte sur {$background}")
            ->and(csRatio($palette['muted'], $palette[$background]))->toBeGreaterThanOrEqual(4.5, "{$key} : texte secondaire sur {$background}");
    }
})->with('clair_sombre');

it('garde lisible le texte pose sur primaire, secondaire et accent', function (string $key) {
    $palette = csPalette($key);

    foreach (['primary', 'secondary', 'accent'] as $role) {
        expect(csRatio($palette[$role.'_content'], $palette[$role]))->toBeGreaterThanOrEqual(4.5, "{$key} : {$role}");
    }
})->with('clair_sombre');

it('garde le primaire, l accent et les bordures visibles sur la surface', function (string $key) {
    $palette = csPalette($key);

    expect(csRatio($palette['primary'], $palette['surface']))->toBeGreaterThanOrEqual(3.0)
        ->and(csRatio($palette['accent'], $palette['surface']))->toBeGreaterThanOrEqual(3.0)
        ->and(csRatio($palette['border'], $palette['surface']))->toBeGreaterThanOrEqual(1.2);
})->with('clair_sombre');

it('garde la coque d administration lisible', function (string $key) {
    $palette = csPalette($key);

    foreach (['admin_nav', 'admin_hover'] as $background) {
        expect(csRatio($palette['admin_text'], $palette[$background]))->toBeGreaterThanOrEqual(4.5, "{$key} : menu sur {$background}")
            ->and(csRatio('#FFFFFF', $palette[$background]))->toBeGreaterThanOrEqual(4.5, "{$key} : texte blanc sur {$background}");
    }
})->with('clair_sombre');

it('est vraiment clair ou vraiment sombre', function () {
    foreach (['bg', 'surface'] as $role) {
        expect(csLuminance(csPalette('clair')[$role]))->toBeGreaterThan(0.85)
            ->and(csLuminance(csPalette('sombre')[$role]))->toBeLessThan(0.03);
    }
});

it('reste dans l univers du logo en Clair : primaire rouge, secondaire quasi noir', function () {
    $palette = csPalette('clair');
    [$r, $g, $b] = array_map('hexdec', str_split(substr($palette['primary'], 1), 2));

    expect($r)->toBeGreaterThanOrEqual(0x90)->and($g)->toBeLessThanOrEqual(0x40)->and($b)->toBeLessThanOrEqual(0x60)
        ->and(csRatio($palette['secondary'], '#FFFFFF'))->toBeGreaterThanOrEqual(12.0);
});

it('surcharge toutes les couleurs d etat en Sombre, et aucune en Clair', function () {
    foreach (CS_STATUS as $key) {
        expect(csPalette('sombre')[$key] ?? null)->toMatch('/^#[0-9A-F]{6}$/', "sombre : {$key}")
            ->and(csPalette('clair'))->not->toHaveKey($key);
    }
});

it('se distingue des autres themes par son fond', function () {
    $backgrounds = [];

    foreach (config('themes.themes') as $key => $theme) {
        $backgrounds[$key] = strtoupper($theme['palette']['bg'] ?? 'defaut');
    }

    expect($backgrounds)->toHaveKeys(['clair', 'sombre'])
        ->and(count(array_unique($backgrounds)))->toBe(count($backgrounds));
});

it('emet les variables de la coque d administration', function () {
    config()->set('themes.themes.cs_fake', ['label' => 'Fake', 'palette' => [
        'admin_bg' => '#0B0D11', 'admin_nav' => '#12151A', 'admin_hover' => '#252A33', 'admin_text' => '#B6BDC9',
    ]]);
    $css = (new ThemeStyleBuilder(new ColorConverter))->css(null, null, 'cs_fake');

    expect($css)->toContain('--admin-bg:#0B0D11;')
        ->and($css)->toContain('--admin-nav:#12151A;')
        ->and($css)->toContain('--admin-hover:#252A33;')
        ->and($css)->toContain('--admin-text:#B6BDC9;');
});

it('n emet pas la coque d administration sans palette ni avec une valeur invalide', function () {
    config()->set('themes.themes.cs_fake', ['label' => 'Fake', 'palette' => ['admin_nav' => 'noir', 'admin_bg' => '#FFF']]);
    $builder = new ThemeStyleBuilder(new ColorConverter);

    expect($builder->css(null, null, 'cs_fake'))->not->toContain('--admin-')
        ->and($builder->css('#C41E2A', '#17181A'))->not->toContain('--admin-');
});

it('ne laisse pas les couleurs personnalisees changer la coque d administration', function () {
    config()->set('themes.themes.cs_fake', ['label' => 'Fake', 'palette' => ['admin_nav' => '#12151A']]);
    $css = (new ThemeStyleBuilder(new ColorConverter))->css(null, null, 'cs_fake', ['admin_nav' => '#FF0000']);

    expect($css)->toContain('--admin-nav:#12151A;')->and($css)->not->toContain('#FF0000');
});
