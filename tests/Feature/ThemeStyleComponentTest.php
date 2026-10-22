<?php

use App\Models\SiteSetting;

function themeSetColors(string $primary, string $secondary): void
{
    SiteSetting::current()
        ->forceFill(['primary_color' => $primary, 'secondary_color' => $secondary])
        ->saveQuietly();
}

function themeStylePosition(string $html, string $needle): int
{
    $pos = strpos($html, $needle);

    return $pos === false ? -1 : $pos;
}

it('injecte les variables du theme sur la page publique', function () {
    themeSetColors('#C41E2A', '#17181A');

    $html = $this->get('/')->getContent();

    expect($html)->toContain('html:root{')
        ->and($html)->toContain('--color-primary-rgb:196 30 42;')
        ->and($html)->toContain('--color-secondary-rgb:23 24 26;');
});

it('applique la couleur primary choisie dans les reglages', function () {
    themeSetColors('#123ABC', '#17181A');

    expect($this->get('/')->getContent())->toContain('--color-primary-rgb:18 58 188;');
});

it('injecte les variables du theme dans l administration', function () {
    themeSetColors('#123ABC', '#17181A');

    $response = $this->actingAs(adminUser())->get(route('admin.dashboard'));

    $response->assertOk();
    expect($response->getContent())->toContain('--color-primary-rgb:18 58 188;');
});

it('retombe sur les defauts du theme quand la valeur stockee est invalide', function () {
    themeSetColors('javascript:alert(1)', '#17181A');

    $html = $this->get('/')->getContent();

    expect($html)->toContain('--color-primary-rgb:196 30 42;')
        ->and($html)->not->toContain('javascript:alert');
});

it('place le bloc du theme dans le head', function () {
    themeSetColors('#C41E2A', '#17181A');

    $html = $this->get('/')->getContent();
    $head = themeStylePosition($html, '<head>');
    $style = themeStylePosition($html, 'html:root{');
    $end = themeStylePosition($html, '</head>');

    expect($head)->toBeGreaterThanOrEqual(0)
        ->and($style)->toBeGreaterThan($head)
        ->and($style)->toBeLessThan($end);
});
