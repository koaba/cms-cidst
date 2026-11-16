<?php

use App\Models\SiteSetting;
use App\Services\Contact\SocialLinks;
use Illuminate\Support\Facades\Blade;

// Lot 3h-1d-3 : reseaux sociaux (Facebook, YouTube, LinkedIn). SocialLinks est la liste affichee ;
// elle DELEGUE Facebook a SiteContact (une seule source) et gere elle-meme YouTube et LinkedIn.
// Meme interrupteur que les coordonnees (footer_show_contacts). Seules les adresses http(s) sortent.
// WhatsApp reste une coordonnee (SiteContact::whatsapp) : il n'est pas repete ici.

const SOCIAL_LINKS_FACEBOOK = 'https://facebook.com/cidst';
const SOCIAL_LINKS_YOUTUBE = 'https://youtube.com/@cidst';
const SOCIAL_LINKS_LINKEDIN = 'https://linkedin.com/company/cidst';

function socialLinksSet(array $attributes): void
{
    SiteSetting::current()->update($attributes);
}

function socialLinksAll(): array
{
    return (new SocialLinks(SiteSetting::current()))->all();
}

function socialLinksHtml(): string
{
    return Blade::render('<x-footer />');
}

function socialLinksSave($test, array $extra = [])
{
    return $test->actingAs(adminUser())->put(route('admin.settings.update'), [
        'hero_title' => 'Titre',
        'show_footer' => '1',
        'footer_show_contacts' => '1',
        ...$extra,
    ]);
}

/** Balise ouvrante du lien dont le texte commence par le libelle, ou une chaine vide. */
function socialLinksAnchor(string $html, string $label): string
{
    preg_match('/<a [^>]*>(?=\s*'.preg_quote($label, '/').')/', $html, $match);

    return $match[0] ?? '';
}

// ---------------------------------------------------------------- service

it('ne propose aucun reseau quand rien n est renseigne', function () {
    $links = new SocialLinks(SiteSetting::current());

    expect($links->all())->toBe([])
        ->and($links->has())->toBeFalse();
});

it('liste Facebook, YouTube et LinkedIn dans cet ordre', function () {
    socialLinksSet([
        'linkedin_url' => SOCIAL_LINKS_LINKEDIN,
        'youtube_url' => SOCIAL_LINKS_YOUTUBE,
        'facebook_url' => SOCIAL_LINKS_FACEBOOK,
    ]);

    $links = socialLinksAll();

    expect(array_column($links, 'key'))->toBe(['facebook', 'youtube', 'linkedin'])
        ->and(array_column($links, 'label'))->toBe(['Facebook', 'YouTube', 'LinkedIn'])
        ->and(array_column($links, 'href'))->toBe([SOCIAL_LINKS_FACEBOOK, SOCIAL_LINKS_YOUTUBE, SOCIAL_LINKS_LINKEDIN]);
});

it('ignore une adresse qui n est pas http ou https', function (string $column, string $value) {
    socialLinksSet([$column => $value]);

    expect(socialLinksAll())->toBe([]);
})->with([
    ['facebook_url', 'ftp://facebook.com/cidst'],
    ['youtube_url', 'javascript:alert(1)'],
    ['linkedin_url', '//linkedin.com/company/cidst'],
    ['youtube_url', 'data:text/html,x'],
    ['linkedin_url', 'mailto:contact@example.org'],
    ['youtube_url', 'tel:+261340000000'],
]);

it('ignore une adresse faite d espaces', function () {
    socialLinksSet(['youtube_url' => '   ']);

    expect(socialLinksAll())->toBe([]);
});

it('masque tous les reseaux avec l interrupteur des coordonnees', function () {
    socialLinksSet([
        'facebook_url' => SOCIAL_LINKS_FACEBOOK,
        'youtube_url' => SOCIAL_LINKS_YOUTUBE,
        'linkedin_url' => SOCIAL_LINKS_LINKEDIN,
        'footer_show_contacts' => true,
    ]);
    expect(socialLinksAll())->toHaveCount(3);

    socialLinksSet(['footer_show_contacts' => false]);

    expect(socialLinksAll())->toBe([]);
});

it('traite une colonne absente du modele en cache comme vide', function () {
    // Un modele sans attributs : ce que voit le code avant cache:clear apres une migration.
    expect((new SocialLinks(new SiteSetting))->all())->toBe([]);
});

// ---------------------------------------------------------------- administration

it('enregistre les adresses YouTube et LinkedIn', function () {
    socialLinksSave($this, ['youtube_url' => SOCIAL_LINKS_YOUTUBE, 'linkedin_url' => SOCIAL_LINKS_LINKEDIN])
        ->assertSessionHasNoErrors();

    expect(SiteSetting::current()->youtube_url)->toBe(SOCIAL_LINKS_YOUTUBE)
        ->and(SiteSetting::current()->linkedin_url)->toBe(SOCIAL_LINKS_LINKEDIN);
});

it('efface une adresse laissee vide', function () {
    socialLinksSet(['youtube_url' => SOCIAL_LINKS_YOUTUBE]);
    // Etat de depart verifie : sans cette ligne le test passerait tant que la colonne n'existe pas.
    expect(SiteSetting::current()->youtube_url)->toBe(SOCIAL_LINKS_YOUTUBE);

    socialLinksSave($this, ['youtube_url' => ''])->assertSessionHasNoErrors();

    expect(SiteSetting::current()->youtube_url)->toBeNull();
});

it('refuse une adresse de reseau qui n est pas http ou https', function (string $field, string $value) {
    socialLinksSave($this, [$field => $value])->assertSessionHasErrors($field);
})->with([
    ['youtube_url', 'javascript:alert(1)'],
    ['youtube_url', 'ftp://youtube.com/cidst'],
    ['linkedin_url', 'mailto:contact@example.org'],
    ['linkedin_url', 'linkedin.com/company/cidst'],
    ['facebook_url', 'ftp://facebook.com/cidst'],
]);

it('propose les champs YouTube et LinkedIn dans les reglages', function () {
    $html = $this->actingAs(adminUser())->get(route('admin.settings.edit'))->assertOk()->getContent();

    expect($html)->toContain('name="youtube_url"')->toContain('name="linkedin_url"');
});

// ---------------------------------------------------------------- pied de page

it('affiche YouTube et LinkedIn dans le pied de page, en nouvel onglet', function () {
    socialLinksSet([
        'youtube_url' => SOCIAL_LINKS_YOUTUBE,
        'linkedin_url' => SOCIAL_LINKS_LINKEDIN,
        'footer_show_contacts' => true,
    ]);

    $html = socialLinksHtml();

    foreach (['YouTube' => SOCIAL_LINKS_YOUTUBE, 'LinkedIn' => SOCIAL_LINKS_LINKEDIN] as $label => $url) {
        expect(socialLinksAnchor($html, $label))
            ->toContain('href="'.e($url).'"')
            ->toContain('target="_blank"')
            ->toContain('rel="noopener noreferrer"')
            ->toContain('min-h-[44px]');
    }

    expect($html)->toContain('(nouvel onglet)');
});

it('affiche un reseau meme sans autre coordonnee', function () {
    socialLinksSet(['youtube_url' => SOCIAL_LINKS_YOUTUBE, 'footer_show_contacts' => true]);

    expect(socialLinksAnchor(socialLinksHtml(), 'YouTube'))->not->toBe('');
});

it('masque les reseaux du pied de page avec l interrupteur', function () {
    socialLinksSet([
        'facebook_url' => SOCIAL_LINKS_FACEBOOK,
        'youtube_url' => SOCIAL_LINKS_YOUTUBE,
        'footer_show_contacts' => true,
    ]);
    // Temoin positif : sans lui, le test d'absence passerait tant que rien n'est affiche.
    expect(socialLinksAnchor(socialLinksHtml(), 'YouTube'))->not->toBe('');

    socialLinksSet(['footer_show_contacts' => false]);
    $html = socialLinksHtml();

    expect(socialLinksAnchor($html, 'YouTube'))->toBe('')
        ->and(socialLinksAnchor($html, 'Facebook'))->toBe('');
});

it('n affiche Facebook qu une seule fois', function () {
    socialLinksSet([
        'facebook_url' => SOCIAL_LINKS_FACEBOOK,
        'youtube_url' => SOCIAL_LINKS_YOUTUBE,
        'footer_show_contacts' => true,
    ]);

    expect(substr_count(socialLinksHtml(), 'data-icon="facebook"'))->toBe(1);
});

it('dessine une icone pour YouTube et LinkedIn', function () {
    socialLinksSet([
        'youtube_url' => SOCIAL_LINKS_YOUTUBE,
        'linkedin_url' => SOCIAL_LINKS_LINKEDIN,
        'footer_show_contacts' => true,
    ]);

    $html = socialLinksHtml();

    foreach (['youtube', 'linkedin'] as $icon) {
        expect($html)->toMatch('/data-icon="'.$icon.'"[^>]*>\s*<path/');
    }
});
