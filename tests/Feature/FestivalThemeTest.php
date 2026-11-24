<?php

use App\Models\SiteSetting;
use App\Services\Theme\ContrastChecker;
use Illuminate\Support\Carbon;

// Lot 3e-bis-2 : pendant la fenetre d'une fete (config/festivals.php), le site PUBLIC prend la palette
// du theme de fete correspondant (config/themes.php, cle 'festive'). L'administration garde son theme,
// les couleurs personnalisees ne s'appliquent pas, et hors fenetre rien ne change.

afterEach(fn () => Carbon::setTestNow());

function ftSecondary(string $html): ?string
{
    return preg_match('/--color-secondary-rgb:([0-9 ]+);/', $html, $m) === 1 ? $m[1] : null;
}

function ftAt(string $date): void
{
    Carbon::setTestNow(Carbon::parse($date, config('app.timezone')));
}

it('relie chaque fete a un theme de fete declare', function () {
    foreach (config('festivals.festivals') as $key => $festival) {
        $theme = config('themes.themes.'.$festival['theme']);

        expect($theme)->toBeArray("le theme {$festival['theme']} de la fete {$key} est absent de config/themes.php")
            ->and($theme['festive'] ?? false)->toBeTrue()
            ->and($theme['palette'])->toBeArray()
            ->and($theme['label'])->toBeString()->not->toBe('');
    }
});

it('garde des textes et des couleurs de marque lisibles sur chaque fond de fete', function () {
    $checker = new ContrastChecker;

    foreach (config('festivals.festivals') as $key => $festival) {
        $p = config('themes.themes.'.$festival['theme'].'.palette');

        foreach (['bg', 'surface'] as $background) {
            foreach (['text', 'muted', 'primary', 'secondary', 'accent'] as $role) {
                expect($checker->ratio($p[$role], $p[$background]))
                    ->toBeGreaterThanOrEqual(4.5, "{$key} : {$role} sur {$background}");
            }
        }

        expect($checker->ratio($p['admin_text'], $p['admin_nav']))->toBeGreaterThanOrEqual(4.5, "{$key} : menu admin")
            ->and($checker->ratio($p['admin_text'], $p['admin_hover']))->toBeGreaterThanOrEqual(4.5, "{$key} : menu admin survole");
    }
});

it('habille le site public aux couleurs de la fete', function (string $date, string $secondary) {
    ftAt($date);

    expect(ftSecondary($this->get('/')->getContent()))->toBe($secondary);
})->with([
    'Noel' => ['2026-12-25', '20 83 45'],
    'Nouvel An (fin d annee)' => ['2026-12-31', '11 27 63'],
    'Nouvel An (janvier)' => ['2027-01-01', '11 27 63'],
    'Paques' => ['2026-04-05', '59 29 90'],
    'Pentecote' => ['2026-05-24', '124 45 18'],
    'Independance' => ['2026-06-26', '0 105 58'],
]);

it('ne change rien hors des fetes', function () {
    ftAt('2026-07-15');

    expect(ftSecondary($this->get('/')->getContent()))->toBe('23 24 26');
});

it('rend le theme choisi des la fin de la fete', function () {
    ftAt('2027-01-03');

    expect(ftSecondary($this->get('/')->getContent()))->toBe('23 24 26');
});

it('laisse l administration sur son theme pendant une fete', function () {
    ftAt('2026-12-25');

    $html = $this->actingAs(adminUser())->get(route('admin.dashboard'))->getContent();

    expect(ftSecondary($html))->toBe('23 24 26');
});

it('peut etre coupe par FESTIVALS_ENABLED', function () {
    config(['festivals.enabled' => false]);
    ftAt('2026-12-25');

    expect(ftSecondary($this->get('/')->getContent()))->toBe('23 24 26');
});

it('passe avant les couleurs personnalisees pendant la fete', function () {
    SiteSetting::current()->forceFill(['theme_colors' => ['secondary' => '#FF00FF']])->saveQuietly();
    ftAt('2026-12-25');

    expect(ftSecondary($this->get('/')->getContent()))->toBe('20 83 45');

    ftAt('2026-07-15');

    expect(ftSecondary($this->get('/')->getContent()))->toBe('255 0 255');
});

it('ne propose pas les themes de fete dans les reglages', function () {
    $html = $this->actingAs(adminUser())->get(route('admin.settings.edit'))->getContent();

    expect($html)->toMatch('/value="sombre"/')
        ->and($html)->not->toContain('value="fete-noel"')
        ->and($html)->not->toContain('value="fete-paques"');
});

it('refuse de choisir un theme de fete a la main', function () {
    $this->actingAs(adminUser())
        ->put(route('admin.settings.update'), ['hero_title' => 'Titre', 'theme' => 'fete-noel'])
        ->assertSessionHasErrors('theme');
});
