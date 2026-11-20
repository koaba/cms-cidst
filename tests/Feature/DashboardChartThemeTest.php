<?php

// Lot 3e-3 : les graphiques du tableau de bord prennent leurs couleurs dans le theme actif.
// La logique est testee par chart-theme-lib.test.js (npm run test:js) ; ici, on garde le branchement :
// aucune couleur en dur ne doit revenir dans dashboard.js.

function dashboardScript(): string
{
    return file_get_contents(resource_path('js/admin/dashboard.js'));
}

it('lit les couleurs des graphiques dans le theme via chart-theme-lib', function () {
    $js = dashboardScript();

    expect($js)->toContain("from './chart-theme-lib.js'")
        ->and($js)->toContain('chartColors(')
        ->and($js)->toContain('chartOptions(')
        ->and($js)->toContain('colors.primary')
        ->and($js)->toContain('colors.primaryFill');
});

it('ne garde aucune couleur en dur dans dashboard.js', function () {
    expect(dashboardScript())->not->toMatch('/#[0-9A-Fa-f]{3,8}\b|rgba?\(/');
});

it('charge dashboard.js sur le tableau de bord', function () {
    expect(file_get_contents(resource_path('views/admin/dashboard.blade.php')))
        ->toContain("@vite('resources/js/admin/dashboard.js')");
});
