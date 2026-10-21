<?php

use App\Models\PageView;

function dashViewAt($when): void
{
    PageView::create([
        'viewable_type' => 'article',
        'viewable_id' => 1,
        'ip_hash' => hash('sha256', (string) microtime(true).random_int(1, PHP_INT_MAX)),
        'user_agent' => 'Pest',
        'viewed_at' => $when,
    ]);
}
it('affiche le libelle de la periode choisie, sans 30 jours en dur', function (string $key, string $label) {
    $html = $this->actingAs(adminUser())->get(route('admin.dashboard', ['period' => $key]))->assertOk()->getContent();

    expect($html)->toContain('Vues du blog — '.$label)
        ->toContain('Top 5 articles ('.$label.')');

    if ($key !== 'month') {
        expect($html)->not->toContain('30 derniers jours');
    }
})->with([
    'week' => ['week', '7 jours'],
    'month' => ['month', '30 jours'],
    'year' => ['year', '12 mois'],
]);

it('propose les trois periodes et marque la periode active', function () {
    $html = $this->actingAs(adminUser())->get(route('admin.dashboard', ['period' => 'week']))->assertOk()->getContent();

    expect($html)
        ->toContain('period=week')
        ->toContain('period=month')
        ->toContain('period=year')
        ->and(substr_count($html, 'aria-current="page"'))->toBe(1);
});

it('affiche nouveau quand la periode precedente est vide', function () {
    dashViewAt(now()->subDay());

    $html = $this->actingAs(adminUser())->get(route('admin.dashboard', ['period' => 'week']))->assertOk()->getContent();

    expect($html)->toContain('data-variation="new"');
});

it('affiche la variation signee quand la periode precedente existe', function () {
    dashViewAt(now()->subDays(8));
    dashViewAt(now()->subDay());
    dashViewAt(now()->subDays(2));

    $html = $this->actingAs(adminUser())->get(route('admin.dashboard', ['period' => 'week']))->assertOk()->getContent();

    expect($html)->toContain('data-variation="up"')->toContain('+100');
});

it('compte toutes les vues en mode global, sans variation', function () {
    dashViewAt(now()->subDays(2));
    dashViewAt(now()->subDays(100));
    dashViewAt(now()->subDays(400));

    $response = $this->actingAs(adminUser())->get(route('admin.dashboard', ['period' => 'all']))->assertOk();
    $html = $response->getContent();

    expect($response->viewData('stats')['views_period'] ?? -1)->toBe(3)
        ->and($html)->not->toContain('data-variation')
        ->toContain('Vues du blog — Global')
        ->toContain('period=all');
});
