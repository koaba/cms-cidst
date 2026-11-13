<?php

use App\Models\SiteSetting;
use App\Services\Contact\SiteContact;
use App\View\Components\Footer;

// Lot 3h-1c : source unique des coordonnees de l'etablissement (adresse, telephones, WhatsApp,
// horaires, e-mail, Facebook, point GPS). Le pied de page, la page Contact et le JSON-LD la lisent.
// Un seul interrupteur (footer_show_contacts) masque tout.

function siteContactMake(array $attributes = []): SiteContact
{
    return new SiteContact(new SiteSetting($attributes));
}

/** Reglages complets, utiles pour comparer deux lectures des memes donnees. */
function siteContactFull(array $extra = []): array
{
    return [
        'contact_address' => "12 rue du Test\nAntananarivo",
        'contact_phone' => '+261 20 22 000 00',
        'contact_phone_2' => '034 12 345 67',
        'contact_whatsapp' => '+261 34 12 345 67',
        'contact_hours' => 'Lun-Ven 8h-17h',
        'contact_public_email' => 'documents@cidst.mg',
        'facebook_url' => 'https://facebook.com/cidst',
        'map_latitude' => -18.9137,
        'map_longitude' => 47.5361,
        ...$extra,
    ];
}

// ---------------------------------------------------------------- point GPS

it('lit le point GPS quand la latitude et la longitude sont renseignees', function () {
    expect(siteContactMake(['map_latitude' => -18.9137, 'map_longitude' => 47.5361])->coordinates())
        ->toBe(['lat' => -18.9137, 'lng' => 47.5361]);
});

it('lit le point GPS depuis des decimales de base de donnees', function () {
    expect(siteContactMake(['map_latitude' => '-18.9137000', 'map_longitude' => '47.5361000'])->coordinates())
        ->toBe(['lat' => -18.9137, 'lng' => 47.5361]);
});

it('accepte zero comme latitude et longitude', function () {
    expect(siteContactMake(['map_latitude' => 0, 'map_longitude' => 0])->coordinates())
        ->toBe(['lat' => 0.0, 'lng' => 0.0]);
});

it('ne donne pas de point GPS quand il en manque une moitie ou qu il est invalide', function (array $attributes) {
    expect(siteContactMake($attributes)->coordinates())->toBeNull();
})->with([
    'rien' => [[]],
    'latitude seule' => [['map_latitude' => -18.9137]],
    'longitude seule' => [['map_longitude' => 47.5361]],
    'latitude trop haute' => [['map_latitude' => 91, 'map_longitude' => 47.5361]],
    'latitude trop basse' => [['map_latitude' => -91, 'map_longitude' => 47.5361]],
    'longitude trop haute' => [['map_latitude' => -18.9137, 'map_longitude' => 181]],
    'longitude trop basse' => [['map_latitude' => -18.9137, 'map_longitude' => -181]],
]);

it('construit le lien d itineraire Google Maps', function () {
    expect(siteContactMake(['map_latitude' => -18.9137, 'map_longitude' => 47.5361])->directionsUrl())
        ->toBe('https://www.google.com/maps/dir/?api=1&destination=-18.9137%2C47.5361');
});

it('construit l adresse de la carte OpenStreetMap integree', function () {
    expect(siteContactMake(['map_latitude' => -18.9137, 'map_longitude' => 47.5361])->osmEmbedUrl())
        ->toBe('https://www.openstreetmap.org/export/embed.html?bbox=47.5311%2C-18.9187%2C47.5411%2C-18.9087&layer=mapnik&marker=-18.9137%2C47.5361');
});

it('ne construit ni itineraire ni carte sans point GPS', function () {
    $contact = siteContactMake(['map_latitude' => -18.9137]);

    expect($contact->directionsUrl())->toBeNull()
        ->and($contact->osmEmbedUrl())->toBeNull();
});

// ---------------------------------------------------------------- interrupteur unique

it('masque toutes les coordonnees avec l interrupteur unique', function () {
    $contact = siteContactMake(siteContactFull(['footer_show_contacts' => false]));

    expect($contact->has())->toBeFalse()
        ->and($contact->addressLines())->toBe([])
        ->and($contact->hoursLines())->toBe([])
        ->and($contact->phones())->toBe([])
        ->and($contact->whatsapp())->toBeNull()
        ->and($contact->email())->toBeNull()
        ->and($contact->facebook())->toBeNull()
        ->and($contact->coordinates())->toBeNull()
        ->and($contact->directionsUrl())->toBeNull()
        ->and($contact->osmEmbedUrl())->toBeNull();
});

it('considere les coordonnees comme affichees par defaut', function () {
    expect(siteContactMake(siteContactFull())->has())->toBeTrue();
});

it('ne compte pas le point GPS comme un moyen de contact', function () {
    expect(siteContactMake(['map_latitude' => -18.9137, 'map_longitude' => 47.5361])->has())->toBeFalse();
});

// ---------------------------------------------------------------- une seule source

it('alimente le pied de page avec les memes coordonnees', function () {
    $settings = new SiteSetting(siteContactFull());
    $footer = new Footer($settings);
    $contact = new SiteContact($settings);

    expect($footer->addressLines())->toBe($contact->addressLines())
        ->and($footer->hoursLines())->toBe($contact->hoursLines())
        ->and($footer->phones())->toBe($contact->phones())
        ->and($footer->whatsapp())->toBe($contact->whatsapp())
        ->and($footer->email())->toBe($contact->email())
        ->and($footer->facebook())->toBe($contact->facebook())
        ->and($footer->hasContacts())->toBe($contact->has());
});

it('lit les reglages courants avec current()', function () {
    SiteSetting::current()->update(['contact_phone' => '0341234567']);

    expect(SiteContact::current()->phones())->toBe([['label' => '0341234567', 'href' => 'tel:0341234567']]);
});
