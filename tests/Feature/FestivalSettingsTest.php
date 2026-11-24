<?php

use App\Models\SiteSetting;
use App\Services\Festival\FestivalSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;

// Lot 3e-bis-4 : Administration > Fetes. Interrupteur en base (le .env reste prioritaire), activation par fete,
// emplacement, icones, animation, et date simulee reservee au Super Admin. Valeurs par defaut : config/festivals.php.

afterEach(fn () => Carbon::setTestNow());

function fsAt(string $date): void
{
    Carbon::setTestNow(Carbon::parse($date, config('app.timezone')));
}

/** @param  array<string, mixed>  $attributes */
function fsSet(array $attributes): void
{
    SiteSetting::current()->forceFill($attributes)->save();
}

function fsDecor(string $variant = 'strip'): string
{
    return Blade::render('<x-festival-decor variant="'.$variant.'" />');
}

function fsSecondary(string $html): ?string
{
    return preg_match('/--color-secondary-rgb:([0-9 ]+);/', $html, $m) === 1 ? $m[1] : null;
}

/**
 * Formulaire complet valide (les cases a cocher envoient 0 ou 1).
 *
 * @param  array<string, mixed>  $noel
 * @param  array<string, mixed>  $top
 * @return array<string, mixed>
 */
function fsPayload(array $noel = [], array $top = []): array
{
    return [
        ...['festivals_enabled' => '1', 'festivals_simulated_on' => null],
        ...$top,
        'festivals' => ['noel' => [
            ...['enabled' => '1', 'placement' => 'both', 'logo' => 'hat', 'strip' => ['hat', 'gift'], 'animated' => '1'],
            ...$noel,
        ]],
    ];
}

const FS_BASE_SECONDARY = '23 24 26';
const FS_NOEL_SECONDARY = '20 83 45';

// --- Interrupteurs -------------------------------------------------------------------------------

it('coupe themes et decors avec l interrupteur de l administration', function () {
    fsAt('2026-12-25');
    fsSet(['festivals_enabled' => false]);

    $html = $this->get('/')->assertOk()->assertDontSee('data-festival-decor', false)->getContent();

    expect(fsSecondary($html))->toBe(FS_BASE_SECONDARY);
});

it('garde le .env prioritaire sur l interrupteur de l administration', function () {
    fsAt('2026-12-25');
    fsSet(['festivals_enabled' => true]);
    config(['festivals.enabled' => false]);

    $html = $this->get('/')->assertOk()->assertDontSee('data-festival-decor', false)->getContent();

    expect(fsSecondary($html))->toBe(FS_BASE_SECONDARY);
});

it('garde les fetes actives si les reglages en cache datent d avant la migration', function () {
    fsAt('2026-12-25');
    unset(SiteSetting::current()->festivals_enabled);

    expect(app(FestivalSettings::class)->enabled())->toBeTrue()
        ->and(app(FestivalSettings::class)->activeKey())->toBe('noel');
});

it('desactive une seule fete sans toucher aux autres', function () {
    fsSet(['festivals_options' => ['noel' => ['enabled' => false]]]);

    fsAt('2026-12-25');
    $html = $this->get('/')->assertOk()->assertDontSee('data-festival-decor', false)->getContent();
    expect(fsSecondary($html))->toBe(FS_BASE_SECONDARY);

    fsAt('2026-06-26');
    $this->get('/')->assertOk()->assertSee('data-festival-decor="independance"', false);
});

// --- Emplacement, icones, animation --------------------------------------------------------------

it('place le decor selon l emplacement choisi', function (string $placement, int $strip, int $logo) {
    fsAt('2026-12-25');
    fsSet(['festivals_options' => ['noel' => ['placement' => $placement]]]);

    $html = $this->get('/')->assertOk()->getContent();

    expect(substr_count($html, 'data-variant="strip"'))->toBe($strip)
        ->and(substr_count($html, 'data-variant="logo"'))->toBe($logo);
})->with([
    'bandeau et logo' => ['both', 1, 1],
    'bandeau seulement' => ['strip', 1, 0],
    'logo seulement' => ['logo', 0, 1],
    'aucun decor' => ['none', 0, 0],
]);

it('garde les couleurs de la fete quand le decor est supprime', function () {
    fsAt('2026-12-25');
    fsSet(['festivals_options' => ['noel' => ['placement' => 'none']]]);

    expect(fsSecondary($this->get('/')->getContent()))->toBe(FS_NOEL_SECONDARY);
});

it('affiche l icone choisie pour le logo', function () {
    fsAt('2026-12-25');
    fsSet(['festivals_options' => ['noel' => ['logo' => 'star']]]);

    $html = fsDecor('logo');

    expect($html)->toContain(trim(view('partials.festival.icons.star', ['class' => 'festival-float h-full w-full'])->render()))
        ->and($html)->not->toContain(trim(view('partials.festival.icons.hat', ['class' => 'festival-float h-full w-full'])->render()));
});

it('repete en cycle les icones choisies pour le bandeau', function () {
    fsAt('2026-12-25');
    fsSet(['festivals_options' => ['noel' => ['strip' => ['star', 'glass']]]]);

    expect(app(FestivalSettings::class)->strip('noel'))->toBe(['star', 'glass', 'star', 'glass', 'star', 'glass', 'star'])
        ->and(substr_count(fsDecor(), '<svg'))->toBe(7)
        ->and(substr_count(fsDecor(), 'hidden sm:block'))->toBe(3);
});

it('coupe l animation quand elle est desactivee', function () {
    fsAt('2026-12-25');

    expect(fsDecor())->toContain('festival-float');

    fsSet(['festivals_options' => ['noel' => ['animated' => false]]]);

    expect(fsDecor())->not->toContain('festival-float')
        ->and(fsDecor('logo'))->not->toContain('festival-float');
});

// --- Simulation de date --------------------------------------------------------------------------

it('reserve la date simulee au Super Admin connecte', function () {
    fsAt('2026-07-15');
    fsSet(['festivals_simulated_on' => '2026-12-25']);

    // Visiteur et Publication : la vraie date, aucune fete.
    $guest = $this->get('/')->assertOk()->assertDontSee('data-festival-decor', false)->getContent();
    expect(fsSecondary($guest))->toBe(FS_BASE_SECONDARY);

    $publication = $this->actingAs(publicationUser())->get('/')->assertOk()->assertDontSee('data-festival-decor', false)->getContent();
    expect(fsSecondary($publication))->toBe(FS_BASE_SECONDARY);

    // Super Admin : le site comme le 25 decembre.
    $admin = $this->actingAs(adminUser())->get('/')->assertOk()->assertSee('data-festival-decor="noel"', false)->getContent();
    expect(fsSecondary($admin))->toBe(FS_NOEL_SECONDARY);
});

it('ne simule rien sans date enregistree', function () {
    fsAt('2026-07-15');

    $this->actingAs(adminUser())->get('/')->assertOk()->assertDontSee('data-festival-decor', false);
});

it('respecte l interrupteur meme pendant une simulation', function () {
    fsAt('2026-07-15');
    fsSet(['festivals_simulated_on' => '2026-12-25', 'festivals_enabled' => false]);

    $this->actingAs(adminUser())->get('/')->assertOk()->assertDontSee('data-festival-decor', false);
});

it('n applique jamais la simulation a l administration', function () {
    fsAt('2026-07-15');
    fsSet(['festivals_simulated_on' => '2026-12-25']);

    $html = $this->actingAs(adminUser())->get(route('admin.dashboard'))->assertOk()->assertDontSee('data-festival-decor', false)->getContent();

    expect(fsSecondary($html))->toBe(FS_BASE_SECONDARY);
});

// --- Liste blanche -------------------------------------------------------------------------------

it('ne croit jamais la saisie : liste blanche des fetes, emplacements et icones', function () {
    $settings = app(FestivalSettings::class);

    $options = $settings->normalize([
        'noel' => [
            'enabled' => '0',
            'placement' => '<script>',
            'logo' => '../../etc/passwd',
            'strip' => ['hat', 'inconnue', ['x'], 'gift'],
            'animated' => 'peut-etre',
        ],
        'inconnue' => ['enabled' => '1'],
    ]);

    expect(array_keys($options))->toBe(['noel'])
        ->and($options['noel'])->toBe([
            'enabled' => false,
            'placement' => 'both',
            'logo' => 'hat',
            'strip' => ['hat', 'gift'],
            'animated' => true,
        ]);
});

it('limite le bandeau a 7 icones et retombe sur le decor par defaut s il est vide', function () {
    $settings = app(FestivalSettings::class);

    $many = $settings->normalize(['noel' => ['strip' => array_fill(0, 9, 'gift')]]);
    $empty = $settings->normalize(['noel' => ['strip' => ['inconnue']]]);

    expect($many['noel']['strip'])->toHaveCount(7)
        ->and($empty['noel']['strip'])->toBe(config('festivals.decors.noel.strip'));
});

it('relit les options enregistrees avec la meme liste blanche', function () {
    fsSet(['festivals_options' => ['noel' => ['logo' => '<script>', 'placement' => 'partout']]]);

    $options = app(FestivalSettings::class)->options('noel');

    expect($options['logo'])->toBe('hat')
        ->and($options['placement'])->toBe('both');
});

it('garde les valeurs par defaut de la config sans aucun reglage', function () {
    foreach (config('festivals.festivals') as $key => $festival) {
        $options = app(FestivalSettings::class)->options($key);
        $decor = config('festivals.decors.'.$festival['decor']);

        expect($options)->toBe([
            'enabled' => true,
            'placement' => 'both',
            'logo' => $decor['logo'],
            'strip' => $decor['strip'],
            'animated' => true,
        ]);
    }
});

it('propose une bibliotheque d icones complete', function () {
    expect(config('festivals.icons'))->not->toBeEmpty();

    foreach (config('festivals.icons') as $key => $label) {
        expect(view()->exists('partials.festival.icons.'.$key))->toBeTrue("partial absent pour $key")
            ->and($label)->toBeString()->not->toBe('');
    }

    foreach (config('festivals.decors') as $decor => $set) {
        foreach ([$set['logo'], ...$set['strip']] as $icon) {
            expect(array_key_exists($icon, config('festivals.icons')))->toBeTrue("icone $icon du decor $decor absente de la bibliotheque");
        }
    }
});

// --- Page d'administration -----------------------------------------------------------------------

it('reserve la page Fetes au Super Admin', function () {
    $this->get(route('admin.festivals.edit'))->assertRedirect();
    $this->actingAs(publicationUser())->get(route('admin.festivals.edit'))->assertForbidden();
    $this->actingAs(publicationUser())->put(route('admin.festivals.update'), fsPayload())->assertForbidden();
    $this->actingAs(adminUser())->get(route('admin.festivals.edit'))->assertOk();
});

it('liste chaque fete avec ses dates dans la page', function () {
    fsAt('2026-11-24');

    $response = $this->actingAs(adminUser())->get(route('admin.festivals.edit'))->assertOk();

    foreach (config('festivals.festivals') as $festival) {
        $response->assertSee($festival['label']);
    }

    $response->assertSee('du 15/12/2026 au 26/12/2026')
        ->assertSee('du 27/12/2026 au 02/01/2027');
});

it('signale quand le .env coupe les fetes et garde la case de base cochee', function () {
    config(['festivals.enabled' => false]);

    $html = $this->actingAs(adminUser())->get(route('admin.festivals.edit'))->assertOk()->assertSee('FESTIVALS_ENABLED=false')->getContent();

    expect($html)->toMatch('/name="festivals_enabled" value="1"[^>]*checked/');
});

it('signale l apercu actif dans la page', function () {
    fsSet(['festivals_simulated_on' => '2026-12-25']);

    $this->actingAs(adminUser())->get(route('admin.festivals.edit'))->assertOk()
        ->assertSee('Aperçu actif')
        ->assertSee('value="2026-12-25"', false);
});

it('enregistre les reglages de la page', function () {
    $this->actingAs(adminUser())
        ->put(route('admin.festivals.update'), fsPayload(
            ['placement' => 'strip', 'logo' => 'star', 'strip' => ['glass', '', 'firework'], 'animated' => '0', 'enabled' => '0'],
            ['festivals_enabled' => '0', 'festivals_simulated_on' => '2026-12-25'],
        ))
        ->assertRedirect(route('admin.festivals.edit'))
        ->assertSessionHasNoErrors();

    $settings = SiteSetting::current();

    expect($settings->festivals_enabled)->toBeFalse()
        ->and($settings->festivals_simulated_on->format('Y-m-d'))->toBe('2026-12-25')
        ->and($settings->festivals_options['noel'])->toBe([
            'enabled' => false,
            'placement' => 'strip',
            'logo' => 'star',
            'strip' => ['glass', 'firework'],
            'animated' => false,
        ]);
});

it('vide la date simulee quand le champ est vide', function () {
    fsSet(['festivals_simulated_on' => '2026-12-25']);

    $this->actingAs(adminUser())->put(route('admin.festivals.update'), fsPayload())->assertSessionHasNoErrors();

    expect(SiteSetting::current()->festivals_simulated_on)->toBeNull();
});

it('refuse une saisie invalide', function (array $payload, string $field) {
    $this->actingAs(adminUser())
        ->put(route('admin.festivals.update'), $payload)
        ->assertSessionHasErrors($field);
})->with([
    'icone de logo inconnue' => [fsPayload(['logo' => 'inconnue']), 'festivals.noel.logo'],
    'icone de bandeau inconnue' => [fsPayload(['strip' => ['hat', 'inconnue']]), 'festivals.noel.strip.1'],
    'trop d icones au bandeau' => [fsPayload(['strip' => array_fill(0, 8, 'hat')]), 'festivals.noel.strip'],
    'emplacement inconnu' => [fsPayload(['placement' => 'partout']), 'festivals.noel.placement'],
    'case a cocher invalide' => [fsPayload(['animated' => 'peut-etre']), 'festivals.noel.animated'],
    'date de simulation invalide' => [fsPayload([], ['festivals_simulated_on' => '25/12/2026']), 'festivals_simulated_on'],
    'interrupteur absent' => [['festivals' => []], 'festivals_enabled'],
    'fete inconnue' => [[...fsPayload(), 'festivals' => ['inconnue' => ['enabled' => '1']]], 'festivals'],
]);
