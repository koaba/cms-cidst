<?php

use App\Models\Menu;
use App\Models\SiteSetting;
use App\Support\PublicLayout;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// Lot 3h-1d : liens du pied de page editables. La table menus recoit une colonne location
// (main, footer, legal) : le menu principal, la colonne Navigation du pied de page et les
// mentions legales de la barre du bas. Les trois sortent de la meme requete.

const FOOTER_LINKS_LEGAL_ARIA = "Mentions l\u{e9}gales";

function footerLinksMenu(string $label, string $location, array $attributes = []): Menu
{
    return Menu::factory()->create([
        'label' => $label,
        'target' => '/'.str($label)->slug(),
        'location' => $location,
        ...$attributes,
    ]);
}

/** Nouvelle requete : PublicLayout est « scoped » et l'application de test est partagee. */
function footerLinksFresh($test, string $uri = '/contact'): string
{
    app()->forgetScopedInstances();

    return $test->get($uri)->assertOk()->getContent();
}

/** Colonne Navigation du pied de page (de la balise nav a sa fermeture), ou une chaine vide. */
function footerLinksNav(string $html): string
{
    preg_match('/<nav aria-label="Pied de page".*?<\/nav>/s', $html, $match);

    return $match[0] ?? '';
}

/** Mentions legales de la barre du bas, ou une chaine vide. */
function footerLinksLegal(string $html): string
{
    preg_match('/<nav aria-label="'.preg_quote(FOOTER_LINKS_LEGAL_ARIA, '/').'".*?<\/nav>/s', $html, $match);

    return $match[0] ?? '';
}

/** Barre du menu principal, ou une chaine vide. */
function footerLinksMainNav(string $html): string
{
    preg_match('/<nav class="bg-cidst-surface.*?<\/nav>/s', $html, $match);

    return $match[0] ?? '';
}

/** Balise ouvrante du lien dont le texte commence par le libelle, ou une chaine vide. */
function footerLinksAnchor(string $segment, string $label): string
{
    preg_match('/<a [^>]*>(?=\s*'.preg_quote($label, '/').')/', $segment, $match);

    return $match[0] ?? '';
}

/** Segment de page ou chercher les liens d'un emplacement donne. */
function footerLinksSegment(string $html, string $location): string
{
    return $location === 'legal' ? footerLinksLegal($html) : footerLinksNav($html);
}

// ---------------------------------------------------------------- colonne Navigation

it('affiche les liens actifs du pied de page apres les liens fixes', function () {
    footerLinksMenu('Plan du site', 'footer', ['target' => '/plan-du-site']);

    $nav = footerLinksNav(footerLinksFresh($this));

    expect($nav)->toContain('Plan du site')
        ->toContain('href="/plan-du-site"')
        ->and(strpos($nav, 'Plan du site'))->toBeGreaterThan(strpos($nav, 'Rechercher'));
});

it('trie les liens du pied de page par ordre', function () {
    footerLinksMenu('Second', 'footer', ['order' => 2]);
    footerLinksMenu('Premier', 'footer', ['order' => 1]);

    $nav = footerLinksNav(footerLinksFresh($this));

    expect(strpos($nav, 'Premier'))->not->toBeFalse()
        ->and(strpos($nav, 'Premier'))->toBeLessThan(strpos($nav, 'Second'));
});

it('masque un lien inactif du pied de page', function () {
    footerLinksMenu('Visible', 'footer');
    footerLinksMenu('Cache', 'footer', ['is_active' => false]);

    expect(footerLinksNav(footerLinksFresh($this)))->toContain('Visible')->not->toContain('Cache');
});

it('garde chaque emplacement a sa place', function () {
    footerLinksMenu('Accueil', 'main');
    footerLinksMenu('Plan du site', 'footer');
    footerLinksMenu('Confidentialite', 'legal');

    $html = footerLinksFresh($this);

    expect(footerLinksMainNav($html))->toContain('Accueil')->not->toContain('Plan du site')->not->toContain('Confidentialite')
        ->and(footerLinksNav($html))->toContain('Plan du site')->not->toContain('Accueil')->not->toContain('Confidentialite')
        ->and(footerLinksLegal($html))->toContain('Confidentialite')->not->toContain('Plan du site')->not->toContain('Accueil');
});

// ---------------------------------------------------------------- mentions legales

it('ajoute les mentions legales dans la barre du bas, avant Haut de page', function () {
    footerLinksMenu('Confidentialite', 'legal', ['target' => '/confidentialite']);

    $html = footerLinksFresh($this);
    $legal = footerLinksLegal($html);

    expect($legal)->toContain('href="/confidentialite"')->toContain('Confidentialite')
        ->and(strpos($html, 'aria-label="'.FOOTER_LINKS_LEGAL_ARIA.'"'))->toBeGreaterThan(strpos($html, 'aria-label="Pied de page"'))
        ->and(strpos($html, 'aria-label="'.FOOTER_LINKS_LEGAL_ARIA.'"'))->toBeLessThan(strpos($html, 'Haut de page'));
});

it('n affiche pas de mentions legales sans lien de cet emplacement', function () {
    footerLinksMenu('Plan du site', 'footer');

    $html = footerLinksFresh($this);

    expect(footerLinksNav($html))->toContain('Plan du site')
        ->and($html)->not->toContain('aria-label="'.FOOTER_LINKS_LEGAL_ARIA.'"');
});

// ---------------------------------------------------------------- liens

it('garde une zone tactile de 44 px sur les liens ajoutes', function (string $location) {
    footerLinksMenu('Lien test', $location);

    $tag = footerLinksAnchor(footerLinksSegment(footerLinksFresh($this), $location), 'Lien test');

    expect($tag)->not->toBe('')->toContain('min-h-[44px]');
})->with(['footer', 'legal']);

it('ouvre un lien externe dans un nouvel onglet et garde un lien interne sur place', function (string $location) {
    footerLinksMenu('Externe', $location, ['target' => 'https://example.org/page']);
    footerLinksMenu('Interne', $location, ['target' => '/interne']);

    $segment = footerLinksSegment(footerLinksFresh($this), $location);

    expect(footerLinksAnchor($segment, 'Externe'))->toContain('target="_blank"')->toContain('rel="noopener noreferrer"')
        ->and($segment)->toContain('(nouvel onglet)')
        ->and(footerLinksAnchor($segment, 'Interne'))->toContain('href="/interne"')->not->toContain('target=');
})->with(['footer', 'legal']);

it('neutralise une cible dangereuse sur tous les emplacements', function (string $location) {
    footerLinksMenu('Piege', $location, ['target' => 'javascript:alert(1)']);

    $html = footerLinksFresh($this);
    $segment = $location === 'main' ? footerLinksMainNav($html) : footerLinksSegment($html, $location);

    expect(footerLinksAnchor($segment, 'Piege'))->toContain('href="#"')
        ->and($html)->not->toContain('javascript:alert');
})->with(['main', 'footer', 'legal']);

it('resout un nom de route en adresse', function () {
    footerLinksMenu('Nous ecrire', 'footer', ['target' => 'contact.show']);

    expect(footerLinksAnchor(footerLinksNav(footerLinksFresh($this)), 'Nous ecrire'))
        ->toContain('href="'.e(route('contact.show')).'"');
});

// ---------------------------------------------------------------- integration

it('charge les trois emplacements en une seule requete sur menus', function () {
    foreach (['main', 'footer', 'legal'] as $location) {
        footerLinksMenu("Lien $location", $location);
    }

    $count = 0;
    DB::listen(function ($query) use (&$count) {
        if (preg_match('/from [`"]?menus[`"]?/i', $query->sql)) {
            $count++;
        }
    });

    $html = footerLinksFresh($this);

    expect($html)->toContain('Lien footer')->toContain('Lien legal')
        ->and($count)->toBe(1);
});

it('masque les liens avec le pied de page', function () {
    footerLinksMenu('Accueil', 'main');
    footerLinksMenu('Plan du site', 'footer');
    footerLinksMenu('Confidentialite', 'legal');
    SiteSetting::current()->update(['show_footer' => false]);

    $html = footerLinksFresh($this);

    expect($html)->toContain('Accueil')
        ->not->toContain('<footer')
        ->not->toContain('Plan du site')
        ->not->toContain('Confidentialite');
});

it('applique tout de suite un nouveau lien', function () {
    footerLinksMenu('Ancien', 'footer');

    expect(footerLinksNav(footerLinksFresh($this)))->toContain('Ancien')->not->toContain('Nouveau');

    footerLinksMenu('Nouveau', 'footer');

    expect(footerLinksNav(footerLinksFresh($this)))->toContain('Nouveau');
});

it('ignore un cache de mise en page ancien sans liens de pied de page', function () {
    // Collections Eloquent, comme dans un vrai cache : PublicLayout type ses retours.
    Cache::put(PublicLayout::CACHE_KEY, [
        'menu' => new Collection,
        'ticker_articles' => new Collection,
        'tickers' => new Collection,
        'sidebar_articles' => new Collection,
    ], 3600);

    $html = footerLinksFresh($this);

    expect($html)->toContain('id="site-footer"')->toContain('Haut de page')
        ->not->toContain('aria-label="'.FOOTER_LINKS_LEGAL_ARIA.'"');
});

it('affiche les liens du pied de page sur les autres pages publiques', function (string $uri) {
    footerLinksMenu('Plan du site', 'footer');
    footerLinksMenu('Confidentialite', 'legal');

    $html = footerLinksFresh($this, $uri);

    expect(footerLinksNav($html))->toContain('Plan du site')
        ->and(footerLinksLegal($html))->toContain('Confidentialite');
})->with(['/', '/blog', '/recherche']);

it('garde un nom de route sur place, sans nouvel onglet', function (string $location) {
    footerLinksMenu('Nous ecrire', $location, ['target' => 'contact.show']);

    $segment = footerLinksSegment(footerLinksFresh($this), $location);
    $tag = footerLinksAnchor($segment, 'Nous ecrire');

    expect($tag)->toContain('href="'.e(route('contact.show')).'"')
        ->not->toContain('target=')
        ->and($segment)->not->toContain('(nouvel onglet)');
})->with(['footer', 'legal']);

it('ignore un element du pied de page qui aurait un parent', function (string $location) {
    $parent = footerLinksMenu('Parent', 'main');
    footerLinksMenu('Orphelin', $location, ['parent_id' => $parent->id]);
    footerLinksMenu('Racine', $location);

    $segment = footerLinksSegment(footerLinksFresh($this), $location);

    expect(footerLinksAnchor($segment, 'Racine'))->not->toBe('')
        ->and(footerLinksAnchor($segment, 'Orphelin'))->toBe('');
})->with(['footer', 'legal']);
