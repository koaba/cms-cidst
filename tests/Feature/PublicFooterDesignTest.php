<?php

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Blade;

// Lot 3h-1e : refonte visuelle du pied de page (bandeau d'appel, grille a 12 colonnes,
// pastilles d'icones, barre du bas). Le contenu et les reglages restent ceux des lots 3h-1 et 3h-1b.

function publicFooterDesignHtml(): string
{
    return Blade::render('<x-footer />');
}

function publicFooterDesignSet(array $attributes): void
{
    SiteSetting::current()->update($attributes);
}

/** Bandeau d'appel (section #footer-cta), ou une chaine vide s'il est absent. */
function publicFooterDesignCta(): string
{
    preg_match('/<section id="footer-cta".*?<\/section>/s', publicFooterDesignHtml(), $match);

    return $match[0] ?? '';
}

/** Colonne « Nous joindre » (du titre a la fin de la liste), ou une chaine vide si elle est absente. */
function publicFooterDesignColumn(): string
{
    preg_match('/Nous joindre.*?<\/ul>/s', publicFooterDesignHtml(), $match);

    return $match[0] ?? '';
}

// ---------------------------------------------------------------- bandeau d'appel

it('affiche le bandeau d appel avec les actions disponibles', function () {
    publicFooterDesignSet([
        'contact_phone' => '0341234567',
        'contact_whatsapp' => '+261341234567',
        'contact_public_email' => 'documents@cidst.mg',
    ]);

    expect(publicFooterDesignCta())
        ->toContain('href="tel:0341234567"')
        ->toContain('Appeler')
        ->toContain('href="https://wa.me/261341234567"')
        ->toContain('WhatsApp')
        ->toContain('href="'.route('contact.show').'"')
        ->toContain("\u{c9}crire");
});

it('place le bandeau d appel avant les colonnes', function () {
    publicFooterDesignSet(['contact_phone' => '0341234567']);

    $html = publicFooterDesignHtml();
    $cta = strpos($html, 'id="footer-cta"');
    $nav = strpos($html, 'aria-label="Pied de page"');

    expect($cta)->not->toBeFalse()
        ->and($nav)->not->toBeFalse()
        ->and($cta)->toBeLessThan($nav);
});

it('n offre dans le bandeau que ce qui est renseigne', function (array $attributes, array $present, array $absent) {
    publicFooterDesignSet($attributes);

    $cta = publicFooterDesignCta();

    expect($cta)->not->toBe('');

    foreach ($present as $needle) {
        expect($cta)->toContain($needle);
    }

    foreach ($absent as $needle) {
        expect($cta)->not->toContain($needle);
    }
})->with([
    'telephone seul' => [['contact_phone' => '0341234567'], ['Appeler', "\u{c9}crire"], ['wa.me']],
    'whatsapp seul' => [['contact_whatsapp' => '+261341234567'], ['WhatsApp', "\u{c9}crire"], ['Appeler', 'tel:']],
    'e-mail seul' => [['contact_public_email' => 'documents@cidst.mg'], ["\u{c9}crire"], ['Appeler', 'tel:', 'wa.me']],
]);

it('n affiche pas le bandeau sans moyen de contact', function (array $attributes) {
    publicFooterDesignSet($attributes);

    expect(publicFooterDesignHtml())->not->toContain('id="footer-cta"');
})->with([
    'rien' => [[]],
    'adresse et horaires seulement' => [['contact_address' => 'Rue du Test', 'contact_hours' => 'Lun-Ven 8h-17h']],
    'telephone trop court' => [['contact_phone' => '12']],
    'facebook seul' => [['facebook_url' => 'https://facebook.com/cidst']],
]);

it('masque le bandeau avec les coordonnees', function () {
    publicFooterDesignSet([
        'contact_phone' => '0341234567',
        'contact_whatsapp' => '+261341234567',
        'contact_public_email' => 'documents@cidst.mg',
        'footer_show_contacts' => false,
    ]);

    expect(publicFooterDesignHtml())->not->toContain('id="footer-cta"')->not->toContain('Appeler');
});

it('met en avant le premier bouton du bandeau seulement', function () {
    publicFooterDesignSet([
        'contact_phone' => '0341234567',
        'contact_whatsapp' => '+261341234567',
        'contact_public_email' => 'documents@cidst.mg',
    ]);

    $cta = publicFooterDesignCta();

    expect(substr_count($cta, 'text-cidst-primary-content'))->toBe(1)
        ->and(strpos($cta, 'text-cidst-primary-content'))->toBeLessThan(strpos($cta, 'wa.me'));
});

it('ouvre le bouton WhatsApp du bandeau dans un nouvel onglet, pas le bouton Appeler', function () {
    publicFooterDesignSet(['contact_phone' => '0341234567', 'contact_whatsapp' => '+261341234567']);

    expect(publicFooterDesignCta())
        ->toContain('href="https://wa.me/261341234567" target="_blank" rel="noopener noreferrer"')
        ->not->toContain('href="tel:0341234567" target');
});

it('garde une zone tactile de 44 px sur chaque lien du bandeau et de la colonne', function () {
    publicFooterDesignSet([
        'contact_phone' => '0341234567',
        'contact_whatsapp' => '+261341234567',
        'contact_public_email' => 'documents@cidst.mg',
        'facebook_url' => 'https://facebook.com/cidst',
    ]);

    foreach ([publicFooterDesignCta(), publicFooterDesignColumn()] as $block) {
        expect($block)->not->toBe('');

        preg_match_all('/<a [^>]*>/', $block, $links);

        expect($links[0])->not->toBeEmpty();

        foreach ($links[0] as $link) {
            expect($link)->toContain('min-h-[44px]');
        }
    }
});

// ---------------------------------------------------------------- colonnes et barre du bas

it('place chaque icone de contact dans une pastille teintee', function () {
    publicFooterDesignSet(['contact_phone' => '0341234567']);

    expect(publicFooterDesignColumn())->toContain('rounded-full bg-cidst-primary/10');
});

it('utilise l icone Facebook pour le lien Facebook', function () {
    publicFooterDesignSet(['facebook_url' => 'https://facebook.com/cidst']);

    expect(publicFooterDesignColumn())->toContain('data-icon="facebook"');
});

it('organise le pied de page sur douze colonnes sans coordonnees', function () {
    expect(publicFooterDesignHtml())
        ->toContain('lg:grid-cols-12')
        ->toContain('lg:col-span-6')
        ->not->toContain('lg:col-span-5');
});

it('repartit les colonnes 4, 3 et 5 avec les coordonnees', function () {
    publicFooterDesignSet(['contact_phone' => '0341234567']);

    expect(publicFooterDesignHtml())
        ->toContain('lg:col-span-4')
        ->toContain('lg:col-span-3')
        ->toContain('lg:col-span-5')
        ->not->toContain('lg:col-span-6');
});

it('affiche le logo en grand format', function () {
    expect(publicFooterDesignHtml())->toContain('h-14 w-auto')->not->toContain('h-10 w-auto');
});

it('propose un retour en haut de page dans la barre du bas', function () {
    expect(publicFooterDesignHtml())
        ->toMatch('/<a href="#"[^>]*min-h-\[44px\][^>]*>Haut de page<\/a>/');
});
