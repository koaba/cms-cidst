<?php

use App\Models\Menu;
use Illuminate\Support\Facades\Schema;

// Lot 3h-1d : emplacement (menu principal, pied de page, mentions legales) d'un element de menu,
// saisi dans /admin/menus. Un lien du pied de page est a plat : pas de menu parent.

function menuLocationAdmin()
{
    return test()->actingAs(adminUser());
}

function menuLocationPost(array $extra = [])
{
    return menuLocationAdmin()->post(route('admin.menus.store'), ['label' => 'Plan', 'target' => '/plan', ...$extra]);
}

function menuLocationMake(string $label, string $location, array $attributes = []): Menu
{
    return Menu::factory()->create(['label' => $label, 'target' => '/'.str($label)->slug(), 'location' => $location, ...$attributes]);
}

/** Liste deroulante des emplacements d'un formulaire, ou une chaine vide. */
function menuLocationSelect(string $html): string
{
    preg_match('/<select[^>]*name="location".*?<\/select>/s', $html, $match);

    return $match[0] ?? '';
}

/** Section de la liste admin d'un emplacement, ou une chaine vide. */
function menuLocationSection(string $html, string $location): string
{
    preg_match('/<section id="menus-'.$location.'".*?<\/section>/s', $html, $match);

    return $match[0] ?? '';
}

// ---------------------------------------------------------------- base et modele

it('cree la colonne location, principale par defaut', function () {
    expect(Schema::hasColumn('menus', 'location'))->toBeTrue();

    expect(Menu::query()->create(['label' => 'X', 'target' => '/x'])->fresh()->location)->toBe('main');
});

it('ne renvoie dans l arbre public que le menu principal', function () {
    $root = menuLocationMake('Racine', 'main');
    menuLocationMake('Enfant', 'main', ['parent_id' => $root->id]);
    menuLocationMake('Plan du site', 'footer');
    menuLocationMake('Confidentialite', 'legal');

    $tree = Menu::activeTree();

    expect($tree->pluck('label')->all())->toBe(['Racine'])
        ->and($tree->first()->children->pluck('label')->all())->toBe(['Enfant']);
});

it('range les elements actifs par emplacement', function () {
    menuLocationMake('Accueil', 'main');
    menuLocationMake('Second', 'footer', ['order' => 2]);
    menuLocationMake('Premier', 'footer', ['order' => 1]);
    menuLocationMake('Inactif', 'footer', ['is_active' => false]);
    menuLocationMake('Confidentialite', 'legal');

    $byLocation = Menu::activeByLocation();

    expect(array_keys($byLocation))->toBe(['main', 'footer', 'legal'])
        ->and($byLocation['main']->pluck('label')->all())->toBe(['Accueil'])
        ->and($byLocation['footer']->pluck('label')->all())->toBe(['Premier', 'Second'])
        ->and($byLocation['legal']->pluck('label')->all())->toBe(['Confidentialite']);
});

it('rend une adresse inoffensive pour une cible dangereuse', function () {
    expect((new Menu(['target' => 'javascript:alert(1)']))->resolved_url)->toBe('#')
        ->and((new Menu(['target' => 'data:text/html,x']))->resolved_url)->toBe('#');
});

it('resout toujours un nom de route et garde un chemin ou une adresse sains', function () {
    expect((new Menu(['target' => 'contact.show']))->resolved_url)->toBe(route('contact.show'))
        ->and((new Menu(['target' => '/contact']))->resolved_url)->toBe('/contact')
        ->and((new Menu(['target' => 'https://example.org/a']))->resolved_url)->toBe('https://example.org/a');
});

// ---------------------------------------------------------------- enregistrement

it('enregistre l emplacement choisi', function (string $location) {
    menuLocationPost(['location' => $location])->assertSessionHasNoErrors()->assertRedirect(route('admin.menus.index'));

    expect(Menu::where('label', 'Plan')->first()->location)->toBe($location)
        ->and(Menu::where('label', 'Plan')->first()->parent_id)->toBeNull();
})->with(['main', 'footer', 'legal']);

it('place un element sans emplacement dans le menu principal', function () {
    menuLocationPost()->assertSessionHasNoErrors();

    expect(Menu::where('label', 'Plan')->first()->location)->toBe('main');
});

it('refuse un emplacement inconnu', function (string $location) {
    menuLocationPost(['location' => $location])->assertSessionHasErrors('location');

    expect(Menu::count())->toBe(0);
})->with(['sidebar', 'Footer', 'footer;x']);

it('refuse un menu parent pour un lien du pied de page', function (string $location) {
    $parent = menuLocationMake('Racine', 'main');

    menuLocationPost(['location' => $location, 'parent_id' => $parent->id])->assertSessionHasErrors('parent_id');

    expect(Menu::count())->toBe(1);
})->with(['footer', 'legal']);

it('refuse un parent qui n est pas du menu principal', function () {
    $parent = menuLocationMake('Lien pied', 'footer');

    menuLocationPost(['location' => 'main', 'parent_id' => $parent->id])->assertSessionHasErrors('parent_id');

    expect(Menu::count())->toBe(1);
});

it('refuse une cible dangereuse', function (string $target) {
    menuLocationPost(['target' => $target])->assertSessionHasErrors('target');

    expect(Menu::count())->toBe(0);
})->with(['javascript:alert(1)', 'data:text/html;base64,PHNjcmlwdD4=', '//evil.example/page']);

it('accepte un nom de route, un chemin et une adresse complete', function (string $target) {
    menuLocationPost(['target' => $target])->assertSessionHasNoErrors()->assertRedirect(route('admin.menus.index'));

    expect(Menu::count())->toBe(1)->and(Menu::first()->target)->toBe($target);
})->with(['blog.index', '/contact', 'https://example.org/a']);

it('deplace un lien sans sous-menu vers le pied de page', function () {
    $menu = menuLocationMake('Plan', 'main');

    menuLocationAdmin()->put(route('admin.menus.update', $menu), ['label' => 'Plan', 'target' => '/plan', 'location' => 'footer'])
        ->assertSessionHasNoErrors();

    expect($menu->fresh()->location)->toBe('footer');
});

it('refuse de sortir du menu principal un element qui a des sous-menus', function () {
    $parent = menuLocationMake('Racine', 'main');
    menuLocationMake('Enfant', 'main', ['parent_id' => $parent->id]);

    menuLocationAdmin()->put(route('admin.menus.update', $parent), ['label' => 'Racine', 'target' => '/racine', 'location' => 'footer'])
        ->assertSessionHasErrors('location');

    expect($parent->fresh()->location)->toBe('main');
});

// ---------------------------------------------------------------- formulaires et liste

it('propose les trois emplacements dans le formulaire de creation, menu principal choisi', function () {
    $select = menuLocationSelect(menuLocationAdmin()->get(route('admin.menus.create'))->assertOk()->getContent());

    expect($select)->toMatch('/<option value="main"\s+selected/')
        ->toContain('value="footer"')->toContain('value="legal"')
        ->toContain('Menu principal')->toContain('Pied de page')->toContain("Mentions l\u{e9}gales");
});

it('preselectionne l emplacement dans le formulaire de modification', function () {
    $menu = menuLocationMake('Plan', 'footer');

    $select = menuLocationSelect(menuLocationAdmin()->get(route('admin.menus.edit', $menu))->assertOk()->getContent());

    expect($select)->toMatch('/<option value="footer"\s+selected/')->not->toMatch('/<option value="main"\s+selected/');
});

it('ne propose comme parent que les elements du menu principal', function () {
    menuLocationMake('Racine', 'main');
    menuLocationMake('Lien pied', 'footer');

    menuLocationAdmin()->get(route('admin.menus.create'))->assertOk()->assertSee('Racine')->assertDontSee('Lien pied');
});

it('groupe la liste par emplacement', function () {
    menuLocationMake('Accueil', 'main');
    menuLocationMake('Plan du site', 'footer');
    menuLocationMake('Confidentialite', 'legal');

    $html = menuLocationAdmin()->get(route('admin.menus.index'))->assertOk()->getContent();

    expect(menuLocationSection($html, 'main'))->toContain('Menu principal')->toContain('Accueil')->not->toContain('Plan du site')
        ->and(menuLocationSection($html, 'footer'))->toContain('Pied de page')->toContain('Plan du site')->not->toContain('Accueil')
        ->and(menuLocationSection($html, 'legal'))->toContain("Mentions l\u{e9}gales")->toContain('Confidentialite')->not->toContain('Plan du site');
});
