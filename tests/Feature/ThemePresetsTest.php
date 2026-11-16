<?php

use App\Models\SiteSetting;

/*
 * Lot 3d-4c : prereglages de couleurs. Chaque prereglage garde l'ADN du logo (rouge vif et noir)
 * et change la temperature des neutres et la couleur d'accent. Tout reste modifiable ensuite
 * par les couleurs personnalisees (theme_colors).
 */
const THEME_PRESET_ROLES = ['primary', 'secondary', 'accent', 'bg', 'surface', 'text', 'muted', 'border'];

function themePresetPalette(string $key): array
{
    $palette = config('themes.themes.'.$key.'.palette');

    expect($palette)->toBeArray("le prereglage {$key} est absent de config/themes.php");

    return $palette;
}

function themePresetContrast(string $a, string $b): float
{
    $luminance = function (string $hex): float {
        $channels = array_map(function (string $part): float {
            $v = hexdec($part) / 255;

            return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }, str_split(substr($hex, 1), 2));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    };

    return (max($luminance($a), $luminance($b)) + 0.05) / (min($luminance($a), $luminance($b)) + 0.05);
}

function themePresetTriplet(string $hex): string
{
    return implode(' ', array_map('hexdec', str_split(substr($hex, 1), 2)));
}

dataset('theme_presets', ['bordeaux', 'ardoise', 'sable']);

it('declare les trois prereglages avec un libelle', function (string $key) {
    expect(config('themes.themes.'.$key.'.label'))->toBeString()->not->toBe('')
        ->and(config('themes.default'))->toBe('cidst');
})->with('theme_presets');

it('fournit tous les roles et les couleurs de texte du prereglage', function (string $key) {
    $palette = themePresetPalette($key);

    foreach (array_merge(THEME_PRESET_ROLES, ['primary_content', 'secondary_content', 'accent_content']) as $role) {
        expect($palette[$role] ?? null)->toMatch('/^#[0-9A-F]{6}$/', "{$key} : {$role} manquant ou invalide");
    }
})->with('theme_presets');

it('garde un texte principal lisible sur le fond et la surface', function (string $key) {
    $palette = themePresetPalette($key);

    foreach (['bg', 'surface'] as $background) {
        expect(themePresetContrast($palette['text'], $palette[$background]))->toBeGreaterThanOrEqual(4.5, "{$key} : texte sur {$background}");
        expect(themePresetContrast($palette['muted'], $palette[$background]))->toBeGreaterThanOrEqual(4.5, "{$key} : texte secondaire sur {$background}");
    }
})->with('theme_presets');

it('garde lisible le texte pose sur primaire, secondaire et accent', function (string $key) {
    $palette = themePresetPalette($key);

    foreach (['primary', 'secondary', 'accent'] as $role) {
        expect(themePresetContrast($palette[$role.'_content'], $palette[$role]))->toBeGreaterThanOrEqual(4.5, "{$key} : {$role}");
    }
})->with('theme_presets');

it('garde la couleur primaire et les bordures visibles sur la surface', function (string $key) {
    $palette = themePresetPalette($key);

    expect(themePresetContrast($palette['primary'], $palette['surface']))->toBeGreaterThanOrEqual(3.0)
        ->and(themePresetContrast($palette['border'], $palette['surface']))->toBeGreaterThanOrEqual(1.2);
})->with('theme_presets');

it('reste dans l univers du logo : primaire rouge et secondaire quasi noir', function (string $key) {
    $palette = themePresetPalette($key);
    [$r, $g, $b] = array_map('hexdec', str_split(substr($palette['primary'], 1), 2));

    expect($r)->toBeGreaterThanOrEqual(0x90)
        ->and($g)->toBeLessThanOrEqual(0x40)
        ->and($b)->toBeLessThanOrEqual(0x60)
        ->and(themePresetContrast($palette['secondary'], '#FFFFFF'))->toBeGreaterThanOrEqual(12.0);
})->with('theme_presets');

it('distingue chaque prereglage par son fond et son accent', function () {
    $signatures = collect(['bordeaux', 'ardoise', 'sable'])->map(fn (string $key) => themePresetPalette($key)['bg'].themePresetPalette($key)['accent']);

    expect($signatures->unique())->toHaveCount(3);
});

it('impose les couleurs du prereglage sur le site public', function (string $key) {
    $palette = themePresetPalette($key);
    SiteSetting::current()->forceFill(['theme' => $key])->saveQuietly();

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('--color-primary-rgb:'.themePresetTriplet($palette['primary']).';')
        ->and($html)->toContain('--color-bg-rgb:'.themePresetTriplet($palette['bg']).';')
        ->and($html)->toContain('--color-text-rgb:'.themePresetTriplet($palette['text']).';');
})->with('theme_presets');

it('laisse les couleurs personnalisees passer apres le prereglage', function (string $key) {
    SiteSetting::current()->forceFill(['theme' => $key, 'theme_colors' => ['primary' => '#123ABC']])->saveQuietly();

    $html = $this->get('/')->getContent();

    expect($html)->toContain('--color-primary-rgb:18 58 188;')
        ->and($html)->toContain('--color-bg-rgb:'.themePresetTriplet(themePresetPalette($key)['bg']).';');
})->with('theme_presets');

it('propose les prereglages dans les reglages et coche celui qui est enregistre', function () {
    SiteSetting::current()->forceFill(['theme' => 'ardoise'])->saveQuietly();

    $html = $this->actingAs(adminUser())->get(route('admin.settings.edit'))->assertOk()->getContent();

    expect($html)->toMatch('/value="ardoise"\s+checked/')
        ->and($html)->toContain('value="bordeaux"')
        ->and($html)->toContain('value="sable"')
        ->and($html)->toMatch('/value="cidst"/');
});

it('enregistre un prereglage depuis les reglages', function (string $key) {
    $this->actingAs(adminUser())
        ->put(route('admin.settings.update'), ['hero_title' => 'Titre', 'theme' => $key])
        ->assertSessionHasNoErrors();

    expect(SiteSetting::current()->fresh()->theme)->toBe($key);
})->with('theme_presets');
