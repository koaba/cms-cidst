<?php

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * adminUser() est désormais définie une seule fois dans tests/Pest.php
 * (partagée par tous les fichiers de test), pour éviter l'erreur
 * "Cannot redeclare adminUser()" quand plusieurs fichiers la définissaient
 * chacun de leur côté.
 */
it('sauvegarde les 4 réglages de filigrane par défaut', function () {
    $admin = adminUser();
    $settings = SiteSetting::current();

    $payload = [
        'hero_title' => 'Titre de test', // champ obligatoire du formulaire, sans lien avec le filigrane
        'video_watermark_default_enabled' => '1',
        'image_watermark_default_enabled' => '1',
        'pdf_watermark_default_enabled' => '1',
        'diaporama_watermark_default_enabled' => '1',
    ];

    // HYPOTHÈSE À VÉRIFIER : route sans paramètre, cohérent avec un réglage
    // singleton (SiteSetting::current()). Si la route attend un paramètre
    // (ex. route('admin.settings.update', $settings)), ajuster ici.
    $response = $this->actingAs($admin)
        ->put(route('admin.settings.update'), $payload);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors(); // distingue explicitement un succès d'un échec de validation

    $settings->refresh();

    expect($settings->video_watermark_default_enabled)->toBeTrue()
        ->and($settings->image_watermark_default_enabled)->toBeTrue()
        ->and($settings->pdf_watermark_default_enabled)->toBeTrue()
        ->and($settings->diaporama_watermark_default_enabled)->toBeTrue();
});

it('remet à false un réglage de filigrane quand la case est décochée', function () {
    $admin = adminUser();
    $settings = SiteSetting::current();
    $settings->update([
        'video_watermark_default_enabled' => true,
        'image_watermark_default_enabled' => true,
        'pdf_watermark_default_enabled' => true,
        'diaporama_watermark_default_enabled' => true,
    ]);

    // Aucune des 4 clés n'est envoyée : simule 4 checkboxes décochées
    // (un navigateur n'envoie jamais une checkbox non cochée).
    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'hero_title' => 'Titre de test', // requis même quand les 4 checkboxes sont décochées
        ]);

    $settings->refresh();

    expect($settings->video_watermark_default_enabled)->toBeFalse()
        ->and($settings->image_watermark_default_enabled)->toBeFalse()
        ->and($settings->pdf_watermark_default_enabled)->toBeFalse()
        ->and($settings->diaporama_watermark_default_enabled)->toBeFalse();
});
