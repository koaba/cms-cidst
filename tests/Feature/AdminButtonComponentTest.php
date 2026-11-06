<?php

use Illuminate\Support\Facades\Blade;

it('rend un vrai lien quand href est fourni', function () {
    $html = Blade::render('<x-admin.button href="/admin">Retour</x-admin.button>');

    expect($html)->toContain('<a href="/admin"')->not->toContain('role="button"');
});

it('garde le style primaire par defaut', function () {
    expect(Blade::render('<x-admin.button>Ok</x-admin.button>'))
        ->toContain('<button type="submit"')
        ->toContain('btn btn-primary text-white');
});

it('applique la variante et la taille demandees', function () {
    $html = Blade::render('<x-admin.button variant="outline" size="sm">Ok</x-admin.button>');

    expect($html)->toContain('btn-outline')->toContain('btn-sm')->not->toContain('btn-primary');
});

it('retombe sur la variante primaire si la variante est inconnue', function () {
    expect(Blade::render('<x-admin.button variant="inconnue">Ok</x-admin.button>'))
        ->toContain('btn btn-primary text-white');
});
