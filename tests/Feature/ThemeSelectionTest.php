<?php

use App\Models\SiteSetting;

beforeEach(function () {
    $this->useTheme = function (string $theme, string $primary = '#123ABC', string $secondary = '#000000'): void {
        SiteSetting::current()
            ->forceFill(['theme' => $theme, 'primary_color' => $primary, 'secondary_color' => $secondary])
            ->saveQuietly();
    };

    $this->contrast = function (string $a, string $b): float {
        $luminance = function (string $hex): float {
            $channels = array_map(function (string $part): float {
                $v = hexdec($part) / 255;

                return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
            }, str_split(substr($hex, 1), 2));

            return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
        };

        [$hi, $lo] = [max($luminance($a), $luminance($b)), min($luminance($a), $luminance($b))];

        return ($hi + 0.05) / ($lo + 0.05);
    };
});

it('utilise le theme cidst par defaut', function () {
    expect(SiteSetting::current()->fresh()->theme)->toBe('cidst')
        ->and(config('themes.default'))->toBe('cidst');
});

it('garde les couleurs des reglages avec le theme cidst', function () {
    ($this->useTheme)('cidst');

    expect($this->get('/')->getContent())->toContain('--color-primary-rgb:18 58 188;');
});

it('impose la palette du theme choisi sur le site public', function () {
    ($this->useTheme)('itadori');

    $html = $this->get('/')->getContent();

    expect($html)->toContain('--color-primary-rgb:230 25 25;')
        ->and($html)->toContain('--color-secondary-rgb:36 51 92;')
        ->and($html)->not->toContain('18 58 188');
});

it('impose la palette du theme choisi dans l administration', function () {
    ($this->useTheme)('itadori');

    $html = $this->actingAs(adminUser())->get(route('admin.dashboard'))->getContent();

    expect($html)->toContain('--color-primary-rgb:230 25 25;');
});

it('fixe les couleurs de texte du theme au lieu de les calculer', function () {
    ($this->useTheme)('itadori');

    $html = $this->actingAs(adminUser())->get(route('admin.dashboard'))->getContent();

    expect($html)->toContain('--pc:100% 0 0;');
});

it('retombe sur cidst quand le theme stocke est inconnu', function () {
    ($this->useTheme)('inconnu.palette');

    $this->get('/')->assertOk()->assertSee('--color-primary-rgb:18 58 188;', false);
});

it('enregistre le theme choisi dans les reglages', function () {
    $this->actingAs(adminUser())->put(route('admin.settings.update'), ['hero_title' => 'Titre', 'theme' => 'itadori'])
        ->assertRedirect(route('admin.settings.edit'))->assertSessionHasNoErrors();

    expect(SiteSetting::current()->fresh()->theme)->toBe('itadori');
});

it('refuse un theme inconnu', function () {
    $this->actingAs(adminUser())->put(route('admin.settings.update'), ['hero_title' => 'Titre', 'theme' => 'inconnu'])
        ->assertSessionHasErrors('theme');

    expect(SiteSetting::current()->fresh()->theme)->toBe('cidst');
});

it('garde le theme existant quand le champ est absent ou vide', function () {
    ($this->useTheme)('itadori');

    $this->actingAs(adminUser())->put(route('admin.settings.update'), ['hero_title' => 'Titre'])
        ->assertRedirect(route('admin.settings.edit'))->assertSessionHasNoErrors();
    expect(SiteSetting::current()->fresh()->theme)->toBe('itadori');

    $this->actingAs(adminUser())->put(route('admin.settings.update'), ['hero_title' => 'Titre', 'theme' => ''])
        ->assertRedirect(route('admin.settings.edit'))->assertSessionHasNoErrors();
    expect(SiteSetting::current()->fresh()->theme)->toBe('itadori');
});

it('propose les themes dans les reglages avec cidst coche par defaut', function () {
    $html = $this->actingAs(adminUser())->get(route('admin.settings.edit'))->assertOk()->getContent();

    expect($html)->toContain('name="theme"')
        ->and($html)->toContain('Itadori')
        ->and($html)->toMatch('/value="cidst"\s+checked/')
        ->and($html)->not->toMatch('/value="itadori"\s+checked/');
});

it('coche le theme enregistre dans les reglages', function () {
    ($this->useTheme)('itadori');

    $html = $this->actingAs(adminUser())->get(route('admin.settings.edit'))->getContent();

    expect($html)->toMatch('/value="itadori"\s+checked/')
        ->and($html)->not->toMatch('/value="cidst"\s+checked/');
});

it('declare des palettes lisibles pour chaque theme', function () {
    $themes = config('themes.themes');

    expect($themes)->toHaveKey('cidst')->toHaveKey('itadori');

    foreach (collect($themes)->filter(fn ($theme) => isset($theme['palette'])) as $key => $theme) {
        foreach (['primary', 'secondary', 'accent'] as $role) {
            $ratio = ($this->contrast)($theme['palette'][$role], $theme['palette'][$role.'_content']);

            expect($ratio)->toBeGreaterThanOrEqual(4.5, "{$key} : {$role} est illisible ({$ratio})");
        }
    }
});
