<?php

use App\Models\SiteSetting;

// Lot 3h-1c : encart « Coordonnees » de la page Contact, carte OpenStreetMap chargee au clic
// (aucune requete vers OpenStreetMap avant le clic) et lien « Itineraire » vers Google Maps.

const CONTACT_LOCATION_EMBED = 'https://www.openstreetmap.org/export/embed.html?bbox=47.5311%2C-18.9187%2C47.5411%2C-18.9087&layer=mapnik&marker=-18.9137%2C47.5361';
const CONTACT_LOCATION_DIRECTIONS = 'https://www.google.com/maps/dir/?api=1&destination=-18.9137%2C47.5361';

function contactLocationSet(array $attributes): void
{
    SiteSetting::current()->update($attributes);
}

function contactLocationPoint(): array
{
    return ['map_latitude' => -18.9137, 'map_longitude' => 47.5361];
}

function contactLocationPage($test): string
{
    return $test->get('/contact')->assertOk()->getContent();
}

/** Encart « Coordonnees » (de la balise ouvrante a la fermante), ou une chaine vide s'il est absent. */
function contactLocationSection(string $html): string
{
    preg_match('/<section id="coordonnees".*?<\/section>/s', $html, $match);

    return $match[0] ?? '';
}

// ---------------------------------------------------------------- encart

it('affiche l encart Coordonnees avec l adresse, les telephones et les horaires', function () {
    contactLocationSet([
        'contact_address' => "12 rue du Test\nAntananarivo",
        'contact_phone' => '0341234567',
        'contact_whatsapp' => '+261341234567',
        'contact_hours' => 'Lun-Ven 8h-17h',
    ]);

    expect(contactLocationSection(contactLocationPage($this)))
        ->toContain("Coordonn\u{e9}es")
        ->toContain('12 rue du Test')
        ->toContain('Antananarivo')
        ->toContain('href="tel:0341234567"')
        ->toContain('href="https://wa.me/261341234567"')
        ->toContain('Lun-Ven 8h-17h');
});

it('echappe l adresse dans l encart', function () {
    contactLocationSet(['contact_address' => '12 rue <b>A</b>']);

    $section = contactLocationSection(contactLocationPage($this));

    expect($section)->toContain('12 rue &lt;b&gt;A&lt;/b&gt;')->not->toContain('<b>A</b>');
});

it('n affiche pas l encart sans coordonnees', function () {
    expect(contactLocationPage($this))->not->toContain('id="coordonnees"');
});

it('n affiche pas l encart avec l interrupteur des coordonnees decoche', function () {
    contactLocationSet([
        'contact_address' => '12 rue du Test',
        'contact_phone' => '0341234567',
        ...contactLocationPoint(),
        'footer_show_contacts' => false,
    ]);

    $html = contactLocationPage($this);

    expect($html)->not->toContain('id="coordonnees"')
        ->not->toContain('12 rue du Test')
        ->not->toContain('data-map-src')
        ->not->toContain('google.com/maps');
});

it('ne repete pas l e-mail public ni son identifiant dans l encart', function () {
    contactLocationSet(['contact_address' => '12 rue du Test', 'contact_public_email' => 'documents@cidst.mg']);

    $html = contactLocationPage($this);

    // Le pied de page affiche aussi l'e-mail : seul l'encart est contraint.
    expect(substr_count($html, 'id="contact_public_email"'))->toBe(1)
        ->and(contactLocationSection($html))->not->toBe('')
        ->and(contactLocationSection($html))->not->toContain('documents@cidst.mg')
        ->and(contactLocationSection($html))->not->toContain('contact_public_email');
});

it('garde une zone tactile de 44 px sur chaque lien de l encart', function () {
    contactLocationSet([
        'contact_phone' => '0341234567',
        'contact_whatsapp' => '+261341234567',
        ...contactLocationPoint(),
    ]);

    $section = contactLocationSection(contactLocationPage($this));

    expect($section)->not->toBe('');

    preg_match_all('/<a [^>]*>/', $section, $links);

    expect($links[0])->not->toBeEmpty();

    foreach ($links[0] as $link) {
        expect($link)->toContain('min-h-[44px]');
    }
});

// ---------------------------------------------------------------- carte au clic

it('propose la carte au clic sans aucune iframe au chargement', function () {
    contactLocationSet(contactLocationPoint());

    $html = contactLocationPage($this);
    $section = contactLocationSection($html);

    expect($section)
        ->toContain('id="nous-trouver"')
        ->toContain('<button type="button"')
        ->toContain('data-map-src="'.e(CONTACT_LOCATION_EMBED).'"')
        ->toContain('Afficher la carte')
        ->and($html)->not->toContain('<iframe')
        ->and($html)->not->toContain(' src="https://www.openstreetmap.org');
});

it('garde une zone tactile de 44 px sur le bouton de la carte', function () {
    contactLocationSet(contactLocationPoint());

    // Le menu mobile a aussi un bouton : on ne regarde que celui de l'encart.
    preg_match('/<button type="button"[^>]*>/', contactLocationSection(contactLocationPage($this)), $button);

    expect($button[0] ?? '')->toContain('min-h-[44px]');
});

it('indique que la carte vient d OpenStreetMap et n est chargee qu au clic', function () {
    contactLocationSet(contactLocationPoint());

    expect(contactLocationSection(contactLocationPage($this)))->toContain('OpenStreetMap');
});

it('affiche le lien Itineraire dans un nouvel onglet', function () {
    contactLocationSet(contactLocationPoint());

    expect(contactLocationSection(contactLocationPage($this)))
        ->toContain('href="'.e(CONTACT_LOCATION_DIRECTIONS).'" target="_blank" rel="noopener noreferrer"')
        ->toContain("Itin\u{e9}raire")
        ->toContain('(nouvel onglet)');
});

it('n affiche ni carte ni itineraire sans point GPS', function () {
    contactLocationSet(['contact_address' => '12 rue du Test']);

    $html = contactLocationPage($this);

    expect(contactLocationSection($html))->toContain('12 rue du Test')
        ->and($html)->not->toContain('id="nous-trouver"')
        ->and($html)->not->toContain('data-map-src')
        ->and($html)->not->toContain("Itin\u{e9}raire");
});

it('affiche la carte sans autre coordonnee', function () {
    contactLocationSet(contactLocationPoint());

    expect(contactLocationPage($this))->toContain('id="coordonnees"')->toContain('id="nous-trouver"');
});

it('n affiche pas la carte quand un seul des deux champs GPS est rempli', function () {
    contactLocationSet(['contact_address' => '12 rue du Test', 'map_latitude' => -18.9137]);

    expect(contactLocationPage($this))->not->toContain('data-map-src');
});

it('laisse le formulaire de contact intact', function () {
    contactLocationSet(['contact_address' => '12 rue du Test', ...contactLocationPoint()]);

    $html = contactLocationPage($this);

    expect($html)->toContain('action="'.route('contact.store').'"')
        ->toContain('name="website"')
        ->toContain('id="attachment_link"');
});
