<?php

// Lot 3f-1b : succes, alerte et boutons publics passent par les jetons cidst-*, plus de couleurs Tailwind en dur.

it('affiche le message envoye de la page contact avec les jetons de succes', function () {
    $html = $this->withSession(['success' => true])->get(route('contact.show'))->assertOk()->getContent();

    expect($html)->toContain('bg-cidst-success-soft text-cidst-success-content')
        ->and($html)->not->toContain('bg-green-50')
        ->and($html)->not->toContain('text-green-800');
});

it('affiche le message de succes de l administration sur le fond doux de succes', function () {
    $html = $this->actingAs(adminUser())
        ->withSession(['success' => 'Reussi de test'])
        ->get(route('admin.categories.index'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('Reussi de test')
        ->and($html)->toContain('bg-cidst-success-soft text-cidst-success-content')
        ->and($html)->not->toContain('bg-green-100');
});

it('affiche l avertissement de la limite de sliders avec le jeton d alerte', function () {
    config(['display.max_sliders' => 0]);

    $html = $this->actingAs(adminUser())->get(route('admin.sliders.index'))->assertOk()->getContent();

    expect($html)->toContain('text-cidst-warning')
        ->and($html)->not->toContain('text-amber-600');
});

it('rend chaque style de bouton public avec la couleur primaire du theme', function (string $style, string $expected) {
    $html = view('pages.blocks._bouton', ['data' => ['label' => 'Aller', 'url' => '/contact', 'style' => $style]])->render();

    expect($html)->toContain($expected)->not->toMatch('/-blue-\d{2,3}\b/');
})->with([
    'primaire' => ['primaire', 'bg-cidst-primary text-cidst-primary-content'],
    'outline' => ['outline', 'border-cidst-primary text-cidst-primary'],
]);

it('rend le lien du bloc pdf et le fond section avec la couleur primaire', function () {
    expect((string) file_get_contents(resource_path('views/pages/blocks/_pdf.blade.php')))->toContain('text-cidst-primary')
        ->and(view('pages.blocks._section_fond', ['data' => ['bg_color' => 'blue', 'text' => 'x']])->render())
        ->toContain('bg-cidst-primary text-cidst-primary-content');
});
