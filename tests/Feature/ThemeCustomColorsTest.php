<?php

use App\Models\SiteSetting;
use App\Services\Theme\ColorConverter;
use App\Services\Theme\ContrastChecker;
use App\Services\Theme\ThemeRoles;
use App\Services\Theme\ThemeStyleBuilder;
use Illuminate\Support\Facades\Schema;

// Lot 3d-4a : couche de donnees des couleurs. Un bloc JSON (site_settings.theme_colors)
// porte les sept roles ; il passe apres les reglages historiques et apres la palette du
// theme. Itadori est retire et le texte secondaire par defaut passe a #4B5057.

function themeCustomCss(?string $primary = '#C41E2A', ?string $secondary = '#17181A', ?string $theme = null, ?array $colors = null): string
{
    return (new ThemeStyleBuilder(new ColorConverter))->css($primary, $secondary, $theme, $colors);
}

function themeCustomUsePreset(): void
{
    config(['themes.themes.essai' => [
        'label' => 'Essai',
        'palette' => [
            'primary' => '#E61919',
            'primary_content' => '#FFFFFF',
            'secondary' => '#24335C',
            'secondary_content' => '#FFFFFF',
            'accent' => '#FFAFBD',
            'accent_content' => '#5C111C',
        ],
    ]]);
}

it('ajoute la colonne theme_colors aux reglages', function () {
    expect(Schema::hasColumn('site_settings', 'theme_colors'))->toBeTrue();
});

it('laisse theme_colors vide sur une ligne neuve', function () {
    expect(SiteSetting::current()->fresh()->theme_colors)->toBeNull();
});

it('relit theme_colors comme un tableau', function () {
    $settings = SiteSetting::current();
    $settings->forceFill(['theme_colors' => ['primary' => '#123ABC', 'bg' => '#101010']])->saveQuietly();

    expect($settings->fresh()->theme_colors)->toEqual(['primary' => '#123ABC', 'bg' => '#101010']);
});

it('applique les couleurs personnalisees sur les roles donnes et garde les defauts des autres', function () {
    $css = themeCustomCss(colors: ['primary' => '#123ABC', 'bg' => '#101010', 'muted' => '#B3B3B3']);

    expect($css)->toContain('--color-primary-rgb:18 58 188;')
        ->toContain('--color-bg-rgb:16 16 16;')
        ->toContain('--color-muted-rgb:179 179 179;')
        ->toContain('--color-surface-rgb:255 255 255;');
});

it('ne change pas le css sans couleurs personnalisees', function () {
    expect(themeCustomCss(colors: null))->toBe(themeCustomCss(colors: []))->toBe(themeCustomCss());
});

it('place les couleurs personnalisees apres les reglages historiques', function () {
    $css = themeCustomCss('#AA0000', '#17181A', null, ['primary' => '#123ABC']);

    expect($css)->toContain('--color-primary-rgb:18 58 188;')->not->toContain('--color-primary-rgb:170 0 0;');
});

it('place les couleurs personnalisees apres la palette du prereglage', function () {
    themeCustomUsePreset();

    $css = themeCustomCss('#AA0000', '#17181A', 'essai', ['primary' => '#123ABC']);

    expect($css)->toContain('--color-primary-rgb:18 58 188;')
        ->toContain('--color-secondary-rgb:36 51 92;');
});

it('garde la palette du prereglage quand rien n est personnalise', function () {
    themeCustomUsePreset();

    $css = themeCustomCss('#AA0000', '#17181A', 'essai');

    expect($css)->toContain('--color-primary-rgb:230 25 25;')->toContain('--pc:100% 0 0;');
});

it('ignore les valeurs invalides et les roles inconnus', function () {
    $css = themeCustomCss(colors: ['primary' => 'red;}a{', 'inconnu' => '#FFFFFF', 'bg' => 42, 'text' => '#12']);

    expect($css)->not->toContain('red;}a{')
        ->toContain('--color-primary-rgb:196 30 42;')
        ->toContain('--color-bg-rgb:241 242 244;')
        ->toContain('--color-text-rgb:23 24 26;');
});

it('recalcule le texte sur primaire quand la couleur personnalisee remplace celle du prereglage', function () {
    themeCustomUsePreset();

    expect(themeCustomCss('#AA0000', '#17181A', 'essai', ['primary' => '#FFE000']))->not->toContain('--pc:100% 0 0;');
});

it('injecte les couleurs personnalisees sur le site public', function () {
    SiteSetting::current()->forceFill(['theme_colors' => ['bg' => '#101010']])->saveQuietly();

    $this->get('/')->assertOk()->assertSee('--color-bg-rgb:16 16 16;', false);
});

it('injecte les couleurs personnalisees dans l administration', function () {
    SiteSetting::current()->forceFill(['theme_colors' => ['bg' => '#101010']])->saveQuietly();

    $this->actingAs(adminUser())->get(route('admin.dashboard'))->assertOk()->assertSee('--color-bg-rgb:16 16 16;', false);
});

it('ignore un theme_colors qui n est pas un tableau', function () {
    SiteSetting::current()->forceFill(['theme_colors' => 'texte'])->saveQuietly();

    $this->get('/')->assertOk()->assertSee('--color-bg-rgb:241 242 244;', false);
});

it('ne touche pas theme_colors quand le formulaire n envoie pas ce champ', function () {
    SiteSetting::current()->forceFill(['theme_colors' => ['bg' => '#101010']])->saveQuietly();

    $this->actingAs(adminUser())
        ->put(route('admin.settings.update'), ['hero_title' => 'Titre'])
        ->assertRedirect(route('admin.settings.edit'))
        ->assertSessionHasNoErrors();

    expect(SiteSetting::current()->fresh()->theme_colors)->toEqual(['bg' => '#101010']);
});

it('garde un texte secondaire lisible par defaut', function () {
    $defaults = ThemeRoles::defaults();
    $checker = new ContrastChecker;

    expect($defaults['muted'])->toBe('#4B5057');

    foreach ([['text', 'bg'], ['text', 'surface'], ['muted', 'bg'], ['muted', 'surface']] as [$foreground, $background]) {
        expect($checker->passes($defaults[$foreground], $defaults[$background]))
            ->toBeTrue("{$foreground} sur {$background} passe sous 4,5");
    }
});

it('a retire le theme Itadori et garde cidst par defaut', function () {
    expect(config('themes.themes'))->not->toHaveKey('itadori')->toHaveKey('cidst')
        ->and(config('themes.default'))->toBe('cidst');
});
