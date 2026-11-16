<?php

// Lot 3f-1 : les messages d'erreur utilisent les jetons cidst-danger, plus les rouges Tailwind en dur.

it('affiche l erreur de validation avec le jeton d erreur', function () {
    $html = $this->actingAs(adminUser())
        ->from(route('admin.settings.edit'))
        ->followingRedirects()
        ->put(route('admin.settings.update'), ['hero_title' => ''])
        ->assertOk()
        ->getContent();

    expect($html)->toContain('text-cidst-danger')
        ->and($html)->not->toContain('text-red-600')
        ->and($html)->not->toContain('text-red-500');
});

it('affiche le message d echec de l administration sur le fond doux d erreur', function () {
    $html = $this->actingAs(adminUser())
        ->withSession(['error' => 'Echec de test'])
        ->get(route('admin.menus.index'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('Echec de test')
        ->and($html)->toContain('bg-cidst-danger-soft text-cidst-danger-content')
        ->and($html)->not->toContain('bg-red-100');
});

it('n expose aucun rouge Tailwind dans les scripts de l administration', function () {
    foreach (['article-form.js', 'pdf-thumbnail.js'] as $file) {
        expect((string) file_get_contents(resource_path('js/admin/'.$file)))->not->toMatch('/-red-\d{2,3}\b/');
    }
});
