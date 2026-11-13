<?php

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Blade;

// Lot 3h-1c : lien « Nous trouver » du pied de page, vers la carte de la page Contact.
// Il n'existe que si le point GPS est complet et si les coordonnees sont affichees.

function publicFooterLocationHtml(): string
{
    return Blade::render('<x-footer />');
}

function publicFooterLocationSet(array $attributes): void
{
    SiteSetting::current()->update($attributes);
}

/** Colonne « Navigation » (de la balise nav a sa fermeture), ou une chaine vide. */
function publicFooterLocationNav(): string
{
    preg_match('/<nav aria-label="Pied de page".*?<\/nav>/s', publicFooterLocationHtml(), $match);

    return $match[0] ?? '';
}

it('ajoute le lien Nous trouver vers la carte de la page Contact', function () {
    publicFooterLocationSet(['map_latitude' => -18.9137, 'map_longitude' => 47.5361]);

    expect(publicFooterLocationNav())
        ->toContain('href="'.route('contact.show').'#nous-trouver"')
        ->toContain('Nous trouver');
});

it('garde une zone tactile de 44 px sur le lien Nous trouver', function () {
    publicFooterLocationSet(['map_latitude' => -18.9137, 'map_longitude' => 47.5361]);

    preg_match('/<a [^>]*#nous-trouver"[^>]*>/', publicFooterLocationNav(), $link);

    expect($link[0] ?? '')->toContain('min-h-[44px]');
});

it('place Nous trouver apres Contact et Rechercher', function () {
    publicFooterLocationSet(['map_latitude' => -18.9137, 'map_longitude' => 47.5361]);

    $nav = publicFooterLocationNav();

    expect(strpos($nav, 'Nous trouver'))->not->toBeFalse()
        ->and(strpos($nav, 'Nous trouver'))->toBeGreaterThan(strpos($nav, 'Rechercher'));
});

it('n affiche pas Nous trouver sans point GPS complet', function (array $attributes) {
    publicFooterLocationSet($attributes);

    expect(publicFooterLocationHtml())->not->toContain('Nous trouver')->not->toContain('#nous-trouver');
})->with([
    'rien' => [[]],
    'latitude seule' => [['map_latitude' => -18.9137]],
    'longitude seule' => [['map_longitude' => 47.5361]],
    'hors bornes' => [['map_latitude' => 95, 'map_longitude' => 47.5361]],
]);

it('n affiche pas Nous trouver avec les coordonnees masquees', function () {
    publicFooterLocationSet(['map_latitude' => -18.9137, 'map_longitude' => 47.5361, 'footer_show_contacts' => false]);

    expect(publicFooterLocationHtml())->not->toContain('Nous trouver');
});

it('ne fait pas apparaitre la colonne Nous joindre avec le seul point GPS', function () {
    publicFooterLocationSet(['map_latitude' => -18.9137, 'map_longitude' => 47.5361]);

    expect(publicFooterLocationHtml())->not->toContain('Nous joindre')->toContain('lg:col-span-6');
});
