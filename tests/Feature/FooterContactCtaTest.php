<?php

use App\Models\SiteSetting;

// Lot 3h-1f : sur la page Contact, le bouton « Ecrire » du bandeau d'appel du pied de page
// pointerait vers la page courante : il y est masque. Ailleurs, il reste.

function footerContactCtaSet(array $attributes): void
{
    SiteSetting::current()->update($attributes);
}

/** Bandeau d'appel (section #footer-cta) de la page demandee, ou une chaine vide s'il est absent. */
function footerContactCtaOn($test, string $url): string
{
    preg_match('/<section id="footer-cta".*?<\/section>/s', $test->get($url)->assertOk()->getContent(), $match);

    return $match[0] ?? '';
}

it('masque Ecrire dans le bandeau sur la page Contact', function () {
    footerContactCtaSet([
        'contact_phone' => '0341234567',
        'contact_whatsapp' => '+261341234567',
        'contact_public_email' => 'documents@cidst.mg',
    ]);

    expect(footerContactCtaOn($this, route('contact.show')))
        ->toContain('Appeler')
        ->toContain('WhatsApp')
        ->not->toContain("\u{c9}crire");
});

it('garde Ecrire dans le bandeau sur les autres pages', function () {
    footerContactCtaSet([
        'contact_phone' => '0341234567',
        'contact_public_email' => 'documents@cidst.mg',
    ]);

    expect(footerContactCtaOn($this, route('search')))
        ->toContain('Appeler')
        ->toContain("\u{c9}crire")
        ->toContain('href="'.route('contact.show').'"');
});

it('n affiche pas de bandeau sur la page Contact quand Ecrire serait la seule action', function () {
    footerContactCtaSet(['contact_public_email' => 'documents@cidst.mg']);

    expect(footerContactCtaOn($this, route('contact.show')))->toBe('')
        ->and(footerContactCtaOn($this, route('search')))->toContain("\u{c9}crire");
});

it('met en avant la premiere action restante du bandeau sur la page Contact', function () {
    footerContactCtaSet(['contact_whatsapp' => '+261341234567']);

    preg_match('/<a [^>]*>/', footerContactCtaOn($this, route('contact.show')), $first);

    expect($first[0] ?? '')->toContain('wa.me')->toContain('bg-cidst-primary');
});
