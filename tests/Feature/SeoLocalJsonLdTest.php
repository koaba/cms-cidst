<?php

use App\Models\Article;
use App\Models\SiteSetting;

// Lot 3h-1c : SEO local. Le JSON-LD Organization de l'accueil reprend adresse postale, telephone,
// e-mail et point GPS depuis la source unique des coordonnees. Les horaires libres ne sont pas
// emis (schema.org attend un format structure). Les auteurs et editeurs des articles restent
// l'Organization de base.

function seoLocalBlocks($test, string $url): array
{
    $html = $test->get($url)->assertOk()->getContent();
    preg_match_all('#<script type="application/ld\+json">\s*(.*?)\s*</script>#s', $html, $matches);

    return array_map(fn (string $json) => json_decode($json, true, flags: JSON_THROW_ON_ERROR), $matches[1]);
}

function seoLocalOrganization($test): array
{
    $organization = collect(seoLocalBlocks($test, '/')[0]['@graph'])->firstWhere('@type', 'Organization');

    expect($organization)->not->toBeNull();

    return $organization;
}

function seoLocalSet(array $attributes): void
{
    SiteSetting::current()->update($attributes);
}

function seoLocalFull(array $extra = []): array
{
    return [
        'contact_address' => "12 rue du Test\nAntananarivo",
        'contact_phone' => '+261 20 22 000 00',
        'contact_public_email' => 'documents@cidst.mg',
        'map_latitude' => -18.9137,
        'map_longitude' => 47.5361,
        ...$extra,
    ];
}

it('enrichit l Organization avec adresse, telephone, e-mail et point GPS', function () {
    seoLocalSet(seoLocalFull());

    $organization = seoLocalOrganization($this);

    expect($organization['address'])->toBe([
        '@type' => 'PostalAddress',
        'streetAddress' => '12 rue du Test, Antananarivo',
        'addressCountry' => 'MG',
    ])
        ->and($organization['telephone'])->toBe('+261 20 22 000 00')
        ->and($organization['email'])->toBe('documents@cidst.mg')
        ->and($organization['geo'])->toBe([
            '@type' => 'GeoCoordinates',
            'latitude' => -18.9137,
            'longitude' => 47.5361,
        ]);
});

it('garde le nom, l url et le logo de l Organization', function () {
    seoLocalSet(seoLocalFull(['logo_path' => 'logos/cidst.png']));

    $organization = seoLocalOrganization($this);

    expect($organization['name'])->toBe(config('app.name'))
        ->and($organization['url'])->toBe(url('/'))
        ->and($organization['logo'])->toBe(asset('storage/logos/cidst.png'));
});

it('n ajoute rien sans coordonnees', function () {
    $organization = seoLocalOrganization($this);

    foreach (['address', 'telephone', 'email', 'geo'] as $key) {
        expect($organization)->not->toHaveKey($key);
    }
});

it('n emet que ce qui est renseigne', function () {
    seoLocalSet(['contact_phone' => '0341234567']);

    $organization = seoLocalOrganization($this);

    expect($organization['telephone'])->toBe('0341234567');

    foreach (['address', 'email', 'geo'] as $key) {
        expect($organization)->not->toHaveKey($key);
    }
});

it('emet le point GPS sans adresse', function () {
    seoLocalSet(['map_latitude' => -18.9137, 'map_longitude' => 47.5361]);

    $organization = seoLocalOrganization($this);

    expect($organization['geo']['latitude'])->toBe(-18.9137)
        ->and($organization)->not->toHaveKey('address');
});

it('n emet pas un point GPS incomplet', function () {
    seoLocalSet(seoLocalFull(['map_longitude' => null]));

    expect(seoLocalOrganization($this))->not->toHaveKey('geo');
});

it('prend le premier telephone valide seulement', function () {
    seoLocalSet(['contact_phone' => '12', 'contact_phone_2' => '034 12 345 67']);

    expect(seoLocalOrganization($this)['telephone'])->toBe('034 12 345 67');
});

it('n emet pas de telephone trop court', function () {
    seoLocalSet(['contact_phone' => '12']);

    expect(seoLocalOrganization($this))->not->toHaveKey('telephone');
});

it('emet toutes les lignes de l adresse sur une ligne', function () {
    seoLocalSet(['contact_address' => "Lot 1\r\n\r\n  Analakely  \nAntananarivo"]);

    expect(seoLocalOrganization($this)['address']['streetAddress'])->toBe('Lot 1, Analakely, Antananarivo');
});

it('lit le pays de l adresse dans la configuration', function () {
    expect(config('seo.organization.country'))->toBe('MG');

    config(['seo.organization.country' => 'FR']);
    seoLocalSet(['contact_address' => '1 rue X']);

    expect(seoLocalOrganization($this)['address']['addressCountry'])->toBe('FR');
});

it('n emet pas les horaires libres', function () {
    seoLocalSet(['contact_hours' => 'Lun-Ven 8h-17h', 'contact_phone' => '0341234567']);

    $organization = seoLocalOrganization($this);

    expect($organization)->not->toHaveKey('openingHours')
        ->and($organization)->not->toHaveKey('openingHoursSpecification');
});

it('masque toutes les donnees locales avec l interrupteur des coordonnees', function () {
    seoLocalSet(seoLocalFull(['footer_show_contacts' => false]));

    $organization = seoLocalOrganization($this);

    foreach (['address', 'telephone', 'email', 'geo'] as $key) {
        expect($organization)->not->toHaveKey($key);
    }
});

it('echappe l adresse pour qu elle ne referme jamais la balise script', function () {
    seoLocalSet(['contact_address' => '</script><b>x</b>']);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->not->toContain('</script><b>')
        ->and(seoLocalOrganization($this)['address']['streetAddress'])->toBe('</script><b>x</b>');
});

it('n alourdit pas l auteur et l editeur des articles', function () {
    seoLocalSet(seoLocalFull());
    $article = Article::factory()->create(['slug' => 'article-local', 'published_at' => now()->subDay()]);

    $data = collect(seoLocalBlocks($this, route('blog.show', $article)))->firstWhere('@type', 'NewsArticle');
    $organization = ['@type' => 'Organization', 'name' => config('app.name'), 'url' => url('/')];

    expect($data['publisher'])->toBe($organization)
        ->and($data['author'])->toBe($organization);
});
