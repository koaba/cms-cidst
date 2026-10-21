<?php

use App\Models\PageView;

function viewAt($when): void
{
    PageView::create([
        'viewable_type' => 'article',
        'viewable_id' => 1,
        'ip_hash' => hash('sha256', (string) microtime(true).random_int(1, PHP_INT_MAX)),
        'user_agent' => 'Pest',
        'viewed_at' => $when,
    ]);
}

it('limite les vues a la periode choisie', function () {
    viewAt(now()->subDays(2));
    viewAt(now()->subDays(20));
    viewAt(now()->subDays(100));

    $admin = adminUser();

    $views = fn (string $p) => $this->actingAs($admin)->get(route('admin.dashboard', ['period' => $p]))
        ->assertOk()->viewData('stats')['views_period'] ?? -1;

    expect($views('week'))->toBe(1)
        ->and($views('month'))->toBe(2)
        ->and($views('year'))->toBe(3);
});

it('garde month par defaut et ignore une periode invalide', function () {
    viewAt(now()->subDays(2));
    viewAt(now()->subDays(20));

    $admin = adminUser();
    $default = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->viewData('stats')['views_period'] ?? -1;
    $invalid = $this->actingAs($admin)->get(route('admin.dashboard', ['period' => 'zzz']))->assertOk()->viewData('stats')['views_period'] ?? -1;

    expect($default)->toBe(2)->and($invalid)->toBe(2);
});

it('adapte le nombre de points de la courbe a la periode', function () {
    $admin = adminUser();

    $points = fn (string $p) => $this->actingAs($admin)->get(route('admin.dashboard', ['period' => $p]))
        ->assertOk()->viewData('activiteVues')->count();

    expect($points('week'))->toBe(7)
        ->and($points('month'))->toBe(30)
        ->and($points('year'))->toBe(12);
});
