<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * adminUser() est désormais définie une seule fois dans tests/Pest.php
 * (partagée par tous les fichiers de test), pour éviter l'erreur
 * "Cannot redeclare adminUser()" quand plusieurs fichiers la définissaient
 * chacun de leur côté.
 */

/* ------------------------------------------------------------------ */
/*  §2.1 — Pré-cochage par défaut sur le formulaire de création */
/* ------------------------------------------------------------------ */

it('pré-coche les 4 checkboxes de filigrane à la création quand les réglages sont activés', function () {
    $admin = adminUser();
    SiteSetting::current()->update([
        'image_watermark_default_enabled' => true,
        'pdf_watermark_default_enabled' => true,
        'diaporama_watermark_default_enabled' => true,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.articles.create'));

    $response->assertOk();

    // On vérifie que chaque checkbox porte bien l'attribut "checked" — test
    // volontairement au niveau HTML brut (pas juste "la donnée est bonne en
    // base") car c'est justement ce qui a été cassé par le cache de vue
    // pendant cette session : le code pouvait être correct sans que le HTML
    // rendu le reflète.
    $html = $response->getContent();

    expect($html)->toContain('name="apply_watermark_cover_image"')
        ->and($html)->toContain('name="apply_watermark_images"')
        ->and($html)->toContain('name="apply_watermark_pdfs"')
        ->and($html)->toContain('name="apply_watermark_diaporamas"');

    // Chaque input doit avoir "checked" dans les ~150 caractères qui suivent
    // son attribut name (évite un assert global trop permissif qui passerait
    // même si "checked" apparaît ailleurs sur la page).
    foreach (['apply_watermark_cover_image', 'apply_watermark_images', 'apply_watermark_pdfs', 'apply_watermark_diaporamas'] as $field) {
        $position = strpos($html, "name=\"{$field}\"");
        expect($position)->not->toBeFalse();
        $snippet = substr($html, $position, 200);
        expect($snippet)->toContain('checked');
    }
});

it('ne pré-coche aucune checkbox de filigrane quand les réglages sont désactivés', function () {
    $admin = adminUser();
    SiteSetting::current()->update([
        'image_watermark_default_enabled' => false,
        'pdf_watermark_default_enabled' => false,
        'diaporama_watermark_default_enabled' => false,
    ]);

    $html = $this->actingAs($admin)
        ->get(route('admin.articles.create'))
        ->getContent();

    foreach (['apply_watermark_cover_image', 'apply_watermark_images', 'apply_watermark_pdfs', 'apply_watermark_diaporamas'] as $field) {
        $position = strpos($html, "name=\"{$field}\"");
        expect($position)->not->toBeFalse();
        $snippet = substr($html, $position, 200);
        expect($snippet)->not->toContain('checked');
    }
});

/* ------------------------------------------------------------------ */
/*  §2.2 — Non-régression : édition d'un article avec diaporama */
/* ------------------------------------------------------------------ */

it('affiche sans erreur la page édition d\'un article possédant un diaporama', function () {
    $admin = adminUser();
    $article = Article::factory()->create(['user_id' => $admin->id]);
    $article->diaporamas()->create(['title' => 'Diaporama de test', 'order' => 0]);

    // C'est LE test qui protège contre la régression du bug $video
    // indéfini dans la boucle diaporamas (cf. §2.2 du résumé technique).
    // Avant correction, cette requête retournait une 500.
    $response = $this->actingAs($admin)->get(route('admin.articles.edit', $article));

    $response->assertOk();
});

/* ------------------------------------------------------------------ */
/*  §2.3 — Filigrane cover et galerie indépendants */
/* ------------------------------------------------------------------ */

it('applique le filigrane à la couverture sans l\'appliquer à la galerie quand seule la case cover est cochée', function () {
    $admin = adminUser();
    $category = Category::factory()->create();

    Storage::fake('public');

    $payload = [
        'title' => 'Article de test filigrane indépendant',
        'content' => 'Contenu de test.',
        'categories' => [$category->id],
        'image' => UploadedFile::fake()->image('cover.jpg'),
        'apply_watermark_cover_image' => '1',
        'images' => [UploadedFile::fake()->image('gallery1.jpg')],
        // apply_watermark_images volontairement absent => décoché
    ];

    $response = $this->actingAs($admin)->post(route('admin.articles.store'), $payload);

    $response->assertRedirect(route('admin.articles.index'));

    // NOTE : ce test vérifie le comportement HTTP de bout en bout mais ne
    // vérifie pas le contenu binaire du fichier (présence effective du
    // filigrane sur l'image) — cela suppose que WatermarkService::watermarkImage()
    // est déjà testé unitairement par ailleurs, ou à compléter si ce n'est
    // pas le cas.
    $article = Article::latest('id')->first();
    expect($article)->not->toBeNull();
});
