<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\PdfCategory;
use App\Models\PdfDocument;

it('affiche une categorie du blog avec ses seuls articles', function () {
    $category = Category::factory()->create();
    Article::factory()->create(['title' => 'Article de la categorie', 'is_published' => true])
        ->categories()->attach($category);
    Article::factory()->create(['title' => 'Article hors categorie', 'is_published' => true]);

    // Le bandeau d'actualites (haut de page) reprend tous les derniers articles : on cible les cartes (<h2>).
    $html = $this->get(route('blog.category', $category->slug))->assertOk()->getContent();

    expect($html)->toMatch('/<h2[^>]*>\s*Article de la categorie\s*<\/h2>/')
        ->not->toMatch('/<h2[^>]*>\s*Article hors categorie\s*<\/h2>/');
});

it('affiche une page statique publiee avec un statut 200', function () {
    $page = Page::factory()->create([
        'title' => 'Page de test',
        'is_published' => true,
    ]);

    $response = $this->get(route('pages.show', $page->slug));

    $response->assertOk();
    $response->assertSee('Page de test');
});

it('affiche la liste des documents pdf avec leurs titres', function () {
    PdfDocument::factory()->create(['title' => 'Document present dans la liste']);

    $this->get(route('documents.index'))
        ->assertOk()
        ->assertSee('Document present dans la liste');
});

it('affiche un document pdf specifique avec un statut 200', function () {
    $document = PdfDocument::factory()->create([
        'title' => 'Document de test',
    ]);

    $response = $this->get(route('documents.show', $document->slug));

    $response->assertOk();
    $response->assertSee('Document de test');
});

it('affiche les documents pdf filtres par categorie sans ceux des autres', function () {
    $pdfCategory = PdfCategory::factory()->create();
    PdfDocument::factory()->create(['title' => 'Document de la categorie', 'pdf_category_id' => $pdfCategory->id]);
    PdfDocument::factory()->create(['title' => 'Document d une autre categorie']);

    $this->get(route('documents.category', $pdfCategory->slug))
        ->assertOk()
        ->assertSee('Document de la categorie')
        ->assertDontSee('Document d une autre categorie');
});
