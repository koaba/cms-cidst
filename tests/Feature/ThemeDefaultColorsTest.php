<?php

use App\Models\SiteSetting;
use App\Services\Theme\ThemeStyleBuilder;
use Illuminate\Support\Facades\DB;

// Lot 3b : la colonne site_settings.primary_color / secondary_color doit avoir pour defaut
// les couleurs du theme (celles du ThemeStyleBuilder), pas les anciennes #E80000 / #1A1A1A.

function themeDefMigration(): object
{
    return require database_path('migrations/2026_11_12_100000_align_default_theme_colors.php');
}

it('donne a une installation neuve les couleurs par defaut du theme', function () {
    $row = SiteSetting::query()->create([])->fresh();

    expect($row->getRawOriginal('primary_color'))->toBe(ThemeStyleBuilder::DEFAULT_PRIMARY)
        ->and($row->getRawOriginal('secondary_color'))->toBe(ThemeStyleBuilder::DEFAULT_SECONDARY);
});

it('produit le meme css pour une installation neuve que pour les defauts du builder', function () {
    $row = SiteSetting::query()->create([])->fresh();
    $builder = app(ThemeStyleBuilder::class);

    $fromDatabase = $builder->css($row->getRawOriginal('primary_color'), $row->getRawOriginal('secondary_color'));

    expect($fromDatabase)->toBe($builder->css(null, null));
});

it('aligne les lignes encore aux anciens defauts et laisse les couleurs choisies', function (string $primary, string $secondary, string $expectedPrimary, string $expectedSecondary) {
    $row = SiteSetting::query()->create([]);
    DB::table('site_settings')->where('id', $row->id)->update([
        'primary_color' => $primary,
        'secondary_color' => $secondary,
    ]);

    themeDefMigration()->alignExistingRows();

    $after = DB::table('site_settings')->where('id', $row->id)->first();
    expect($after->primary_color)->toBe($expectedPrimary)
        ->and($after->secondary_color)->toBe($expectedSecondary);
})->with([
    'anciens defauts alignes' => ['#E80000', '#1A1A1A', '#C41E2A', '#17181A'],
    'primaire personnalisee conservee' => ['#123ABC', '#1A1A1A', '#123ABC', '#1A1A1A'],
    'secondaire personnalisee conservee' => ['#E80000', '#000000', '#E80000', '#000000'],
    'deja aux nouvelles valeurs' => ['#C41E2A', '#17181A', '#C41E2A', '#17181A'],
]);
