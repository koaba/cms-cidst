<?php

use App\Models\Menu;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Schema;

// Lot 3h-2 : recherche dans le menu public. Une loupe a l'extremite droite du menu (formulaire GET
// vers /recherche qui fonctionne sans JavaScript), le meme formulaire dans le menu mobile,
// et un reglage admin « Afficher la recherche dans le menu ».

const MENU_SEARCH_CHECKED = 'value="1" class="h-4 w-4" checked';

function menuSearchSet(array $attributes): void
{
    SiteSetting::current()->update($attributes);
}

function menuSearchSave($test, array $extra = [])
{
    return $test->actingAs(adminUser())->put(route('admin.settings.update'), [
        'hero_title' => 'Titre',
        'show_footer' => '1',
        'footer_show_contacts' => '1',
        ...$extra,
    ]);
}

/** Barre de navigation du menu (de la balise nav a sa fermeture), ou une chaine vide. */
function menuSearchNav($test, string $uri = '/contact'): string
{
    preg_match('/<nav class="bg-cidst-surface.*?<\/nav>/s', $test->get($uri)->assertOk()->getContent(), $match);

    return $match[0] ?? '';
}

/** Formulaire de recherche d'un id donne, ou une chaine vide. */
function menuSearchForm(string $nav, string $id = 'menu-search'): string
{
    preg_match('/<form id="'.preg_quote($id, '/').'"[^>]*>.*?<\/form>/s', $nav, $match);

    return $match[0] ?? '';
}

// ---------------------------------------------------------------- formulaire du menu (bureau)

it('envoie la recherche en GET vers la page de recherche', function () {
    $form = menuSearchForm(menuSearchNav($this));

    preg_match('/<form [^>]*>/', $form, $open);

    expect($open[0] ?? '')
        ->toContain('method="get"')
        ->toContain('action="'.e(route('search')).'"')
        ->toContain('role="search"');
});

it('envoie le mot cle dans le champ q avec une etiquette', function () {
    $form = menuSearchForm(menuSearchNav($this));

    preg_match('/<input [^>]*>/', $form, $input);

    expect($input[0] ?? '')
        ->toContain('type="search"')
        ->toContain('name="q"')
        ->toContain('maxlength="255"')
        ->toContain('id="menu-search-q"')
        ->and($form)->toMatch('/<label for="menu-search-q" class="sr-only">Rechercher<\/label>/');
});

it('garde une loupe tactile de 44 px avec un nom accessible', function () {
    $form = menuSearchForm(menuSearchNav($this));

    preg_match('/<button [^>]*>/', $form, $button);

    expect($button[0] ?? '')
        ->toContain('type="submit"')
        ->toContain('aria-label="Rechercher"')
        ->toContain('min-h-[44px]')
        ->toContain('min-w-[44px]')
        ->and($form)->toContain('<svg')->toContain('aria-hidden="true"');
});

it('laisse le champ visible sans JavaScript', function () {
    preg_match('/<input [^>]*>/', menuSearchForm(menuSearchNav($this)), $input);

    expect($input[0] ?? '')->not->toBe('')
        ->not->toContain('type="hidden"')
        ->not->toMatch('/class="[^"]*\b(?:hidden|sr-only)\b/');
});

it('n utilise que les couleurs du theme dans le formulaire', function () {
    $form = menuSearchForm(menuSearchNav($this));

    expect($form)->not->toBe('')
        ->toContain('border-cidst-border')
        ->toContain('text-cidst-text')
        ->toContain('focus-visible:ring-cidst-primary')
        ->not->toMatch('/\b(?:bg|text|border|ring)-(?:white|black|gray|red|blue|green)\b/');
});

it('place la recherche a l extremite droite du menu', function () {
    Menu::factory()->create(['label' => 'Alpha', 'target' => '/alpha', 'is_active' => true]);
    Menu::factory()->create(['label' => 'Omega', 'target' => '/omega', 'is_active' => true]);

    $nav = menuSearchNav($this);
    $form = strpos($nav, '<form id="menu-search"');

    expect($form)->not->toBeFalse()
        ->and($form)->toBeGreaterThan(strpos($nav, 'Omega'))
        ->and($form)->toBeLessThan(strpos($nav, 'id="mobile-menu-toggle"'));
});

// ---------------------------------------------------------------- menu mobile

it('reprend la recherche dans le menu mobile', function () {
    $nav = menuSearchNav($this);
    $mobile = substr($nav, (int) strpos($nav, 'id="mobile-menu"'));
    $form = menuSearchForm($mobile, 'menu-search-mobile');

    preg_match('/<form [^>]*>/', $form, $open);
    preg_match('/<input [^>]*>/', $form, $input);

    expect($form)->not->toBe('')
        ->and($open[0] ?? '')->toContain('method="get"')->toContain('action="'.e(route('search')).'"')->toContain('role="search"')
        ->and($input[0] ?? '')->toContain('name="q"')->toContain('id="menu-search-q-mobile"')
        ->and($form)->toMatch('/<label for="menu-search-q-mobile" class="sr-only">Rechercher<\/label>/')
        ->and($form)->toContain('min-h-[44px]');
});

// ---------------------------------------------------------------- pages et reglage

it('affiche la recherche dans le menu de chaque page publique', function (string $uri) {
    expect(menuSearchForm(menuSearchNav($this, $uri)))->not->toBe('');
})->with(['/', '/contact', '/blog', '/pages', '/recherche', '/documents', '/sliders']);

it('masque la recherche quand le reglage est decoche', function () {
    expect(menuSearchForm(menuSearchNav($this)))->not->toBe('');

    menuSearchSet(['show_search' => false]);

    $nav = menuSearchNav($this);

    expect($nav)->not->toBe('')
        ->and($nav)->not->toContain('role="search"')
        ->and($nav)->not->toContain('menu-search');
});

it('traite une colonne absente du modele en cache comme affichee', function () {
    $html = view('components.main-menu', ['items' => collect(), 'settings' => new SiteSetting])->render();

    expect($html)->toContain('<form id="menu-search"');
});

// ---------------------------------------------------------------- script

it('gere la loupe par un script en ligne', function () {
    $html = $this->get('/contact')->assertOk()->getContent();
    $nav = (int) strpos($html, '</nav>');

    expect(substr($html, $nav))->toMatch('/<script>(?:(?!<\/script>).)*menu-search/s');
});

// ---------------------------------------------------------------- administration

it('cree la colonne show_search, affichee par defaut', function () {
    expect(Schema::hasColumn('site_settings', 'show_search'))->toBeTrue();

    expect(SiteSetting::query()->create([])->fresh()->show_search)->toBeTrue();
});

it('affiche la case Afficher la recherche, cochee par defaut', function () {
    $this->actingAs(adminUser())->get(route('admin.settings.edit'))->assertOk()
        ->assertSee('name="show_search" '.MENU_SEARCH_CHECKED, false)
        ->assertSee('Afficher la recherche dans le menu');
});

it('enregistre la case cochee et l applique tout de suite', function () {
    menuSearchSet(['show_search' => false]);

    menuSearchSave($this, ['show_search' => '1'])->assertSessionHasNoErrors();

    expect(SiteSetting::current()->fresh()->show_search)->toBeTrue();

    $this->get('/contact')->assertOk()->assertSee('role="search"', false);
});

it('decoche une case absente du formulaire et l applique tout de suite', function () {
    menuSearchSave($this)->assertSessionHasNoErrors();

    expect(SiteSetting::current()->fresh()->show_search)->toBeFalse();

    $this->get('/contact')->assertOk()->assertDontSee('role="search"', false);
});

it('garde la case decochee apres une erreur de validation', function () {
    $this->actingAs(adminUser())
        ->from(route('admin.settings.edit'))
        ->put(route('admin.settings.update'), ['show_footer' => '1'])
        ->assertSessionHasErrors('hero_title');

    $this->get(route('admin.settings.edit'))->assertOk()
        ->assertSee('name="show_search"', false)
        ->assertDontSee('name="show_search" '.MENU_SEARCH_CHECKED, false);
});
