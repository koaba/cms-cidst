<?php

use App\Models\SiteSetting;
use App\View\Components\Footer;
use Illuminate\Support\Facades\Schema;

// Lot 3h-1 : pied de page public (composant x-footer, inclus dans le layout) et ses reglages
// (Super Admin) : affichage, description, e-mail et Facebook, texte du copyright. La recherche
// dans le menu est le lot 3h-2. Les contenus sont testes sur le composant seul ; l'integration
// (place dans la page, cache des reglages) sur de vraies pages publiques.

/** Parametre minimal du formulaire des reglages (hero_title est obligatoire). */
function publicFooterPayload(array $extra = []): array
{
    return ['hero_title' => 'Titre', ...$extra];
}

function publicFooterSet(array $attributes): void
{
    SiteSetting::current()->update($attributes);
}

function publicFooterHtml($test): string
{
    return (string) $test->blade('<x-footer />');
}

function publicFooterSave($test, array $extra = [])
{
    return $test->actingAs(adminUser())->put(route('admin.settings.update'), publicFooterPayload($extra));
}

const PUBLIC_FOOTER_CHECKED = 'value="1" class="h-4 w-4" checked';

// ---------------------------------------------------------------- contenu du composant

it('affiche le pied de page par defaut avec les liens Contact et Rechercher', function () {
    $html = publicFooterHtml($this);

    expect($html)
        ->toContain('<footer id="site-footer"')
        ->toContain('aria-label="Pied de page"')
        ->toContain('href="'.route('contact.show').'"')
        ->toContain('href="'.route('search').'"');
});

it('ne rend rien quand le pied de page est masque', function () {
    publicFooterSet(['show_footer' => false]);

    expect(publicFooterHtml($this))->not->toContain('<footer');
});

it('traite une colonne absente du modele en cache comme affichee', function () {
    expect((new Footer(new SiteSetting))->shouldRender())->toBeTrue()
        ->and((new Footer(new SiteSetting))->showContacts())->toBeTrue();
});

it('affiche la description echappee', function () {
    publicFooterSet(['footer_description' => '<b>Bonjour</b> & merci']);

    expect(publicFooterHtml($this))
        ->toContain('&lt;b&gt;Bonjour&lt;/b&gt; &amp; merci')
        ->not->toContain('<b>Bonjour');
});

it('n affiche pas de paragraphe pour une description vide', function () {
    publicFooterSet(['footer_description' => '   ']);

    expect(publicFooterHtml($this))->not->toContain('leading-relaxed');
});

it('affiche l e-mail public et le lien Facebook', function () {
    publicFooterSet(['contact_public_email' => 'documents@cidst.mg', 'facebook_url' => 'https://facebook.com/cidst']);

    expect(publicFooterHtml($this))
        ->toContain('href="mailto:documents@cidst.mg"')
        ->toContain('href="https://facebook.com/cidst"')
        ->toContain('target="_blank" rel="noopener noreferrer"');
});

it('masque l e-mail et Facebook quand l option est decochee', function () {
    publicFooterSet([
        'contact_public_email' => 'documents@cidst.mg',
        'facebook_url' => 'https://facebook.com/cidst',
        'footer_show_contacts' => false,
    ]);

    expect(publicFooterHtml($this))
        ->not->toContain('mailto:')
        ->not->toContain('facebook.com')
        ->not->toContain('Nous joindre');
});

it('n affiche pas la colonne Nous joindre sans e-mail ni Facebook', function () {
    expect(publicFooterHtml($this))->not->toContain('Nous joindre');
});

it('ne transforme en lien qu une adresse http ou https', function (string $url) {
    publicFooterSet(['facebook_url' => $url]);

    expect(publicFooterHtml($this))->not->toContain($url)->not->toContain('Nous joindre');
})->with(['javascript:alert(1)', 'data:text/html,x', 'ftp://exemple.mg/page']);

it('ne reprend pas l identifiant du champ e-mail de la page Contact', function () {
    publicFooterSet(['contact_public_email' => 'documents@cidst.mg']);

    expect(publicFooterHtml($this))->not->toContain('id="contact_public_email"');
});

it('affiche le copyright par defaut avec l annee en cours et le nom du site', function () {
    config(['app.name' => 'Mon Site']);
    $this->travelTo(now()->setDate(2031, 6, 15));

    expect(publicFooterHtml($this))->toContain("\u{a9} 2031 Mon Site");
});

it('remplace les jetons du copyright personnalise', function () {
    config(['app.name' => 'Mon Site']);
    $this->travelTo(now()->setDate(2031, 6, 15));
    publicFooterSet(['footer_copyright' => "\u{a9} {annee} - {site} - Tous droits reserves"]);

    expect(publicFooterHtml($this))->toContain("\u{a9} 2031 - Mon Site - Tous droits reserves");
});

it('affiche un copyright sans jeton tel quel et echappe', function () {
    publicFooterSet(['footer_copyright' => '<i>Libre</i>']);

    expect(publicFooterHtml($this))->toContain('&lt;i&gt;Libre&lt;/i&gt;')->not->toContain('<i>Libre');
});

it('utilise le logo des reglages avec ses dimensions reelles', function () {
    publicFooterSet(['logo_path' => 'logos/test.png', 'logo_width' => 300, 'logo_natural_height' => 120]);

    expect(publicFooterHtml($this))
        ->toContain('storage/logos/test.png')
        ->toContain('width="300" height="120"');
});

it('reserve la place du logo par defaut', function () {
    expect(publicFooterHtml($this))
        ->toContain('images/logo-cidst.png')
        ->toContain('width="189" height="144"');
});

it('reste sur les jetons du theme sans couleur en dur', function () {
    $source = file_get_contents(resource_path('views/components/footer.blade.php'));

    expect($source)
        ->toContain('bg-cidst-surface')
        ->toContain('border-cidst-border')
        ->toContain('text-cidst-muted')
        ->not->toMatch('/\b(?:bg|text|border)-(?:white|black|gray|red|blue|green)\b/');
});

// ---------------------------------------------------------------- integration

it('place le pied de page apres le contenu principal', function (string $uri) {
    $html = $this->get($uri)->assertOk()->getContent();

    $main = strpos($html, '</main>');
    $footer = strpos($html, '<footer id="site-footer"');

    expect($main)->not->toBeFalse()
        ->and($footer)->not->toBeFalse()
        ->and($footer)->toBeGreaterThan($main);
})->with(['/', '/contact', '/blog', '/pages', '/recherche', '/documents', '/sliders']);

it('applique tout de suite l affichage choisi dans les reglages', function () {
    publicFooterSave($this)->assertSessionHasNoErrors();
    $this->get('/contact')->assertOk()->assertDontSee('id="site-footer"', false);

    publicFooterSave($this, ['show_footer' => '1'])->assertSessionHasNoErrors();
    $this->get('/contact')->assertOk()->assertSee('id="site-footer"', false);
});

// ---------------------------------------------------------------- reglages

it('cree les colonnes du pied de page avec leurs valeurs par defaut', function () {
    expect(Schema::hasColumns('site_settings', ['show_footer', 'footer_description', 'footer_show_contacts', 'footer_copyright']))->toBeTrue();

    $settings = SiteSetting::query()->create([])->fresh();

    expect($settings->show_footer)->toBeTrue()
        ->and($settings->footer_show_contacts)->toBeTrue()
        ->and($settings->footer_description)->toBeNull()
        ->and($settings->footer_copyright)->toBeNull();
});

it('affiche les champs du pied de page, coches par defaut', function () {
    $this->actingAs(adminUser())->get(route('admin.settings.edit'))->assertOk()
        ->assertSee('name="show_footer" '.PUBLIC_FOOTER_CHECKED, false)
        ->assertSee('name="footer_show_contacts" '.PUBLIC_FOOTER_CHECKED, false)
        ->assertSee('id="footer_description"', false)
        ->assertSee('id="footer_copyright"', false);
});

it('enregistre les reglages du pied de page', function () {
    publicFooterSave($this, [
        'show_footer' => '1',
        'footer_show_contacts' => '1',
        'footer_description' => 'Centre de documentation',
        'footer_copyright' => "\u{a9} {annee} CIDST",
    ])->assertSessionHasNoErrors();

    $settings = SiteSetting::current()->fresh();

    expect($settings->show_footer)->toBeTrue()
        ->and($settings->footer_show_contacts)->toBeTrue()
        ->and($settings->footer_description)->toBe('Centre de documentation')
        ->and($settings->footer_copyright)->toBe("\u{a9} {annee} CIDST");
});

it('decoche une case absente du formulaire', function () {
    publicFooterSave($this)->assertSessionHasNoErrors();

    $settings = SiteSetting::current()->fresh();

    expect($settings->show_footer)->toBeFalse()
        ->and($settings->footer_show_contacts)->toBeFalse();
});

it('refuse une description ou un copyright trop longs', function (string $field) {
    publicFooterSave($this, [$field => str_repeat('a', 256)])->assertSessionHasErrors($field);
})->with(['footer_description', 'footer_copyright']);

it('garde une case decochee apres une erreur de validation', function () {
    $this->actingAs(adminUser())
        ->from(route('admin.settings.edit'))
        ->put(route('admin.settings.update'), ['footer_show_contacts' => '1'])
        ->assertSessionHasErrors('hero_title');

    $this->get(route('admin.settings.edit'))->assertOk()
        ->assertSee('name="footer_show_contacts" '.PUBLIC_FOOTER_CHECKED, false)
        ->assertDontSee('name="show_footer" '.PUBLIC_FOOTER_CHECKED, false)
        ->assertSee('name="show_footer"', false);
});
