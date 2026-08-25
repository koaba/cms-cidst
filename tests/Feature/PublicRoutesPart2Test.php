<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\PdfCategory;
use App\Models\PdfDocument;

it('affiche une categorie du blog avec un statut 200', function () {
    $category = Category::factory()->create();
    Article::factory()->create(['is_published' => true])
        ->categories()->attach($category);

    $response = $this->get(route('blog.category', $category->slug));

    $response->assertOk();
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

it('affiche la liste des documents pdf avec un statut 200', function () {
    PdfDocument::factory()->create();

    $response = $this->get(route('documents.index'));

    $response->assertOk();
});

it('affiche un document pdf specifique avec un statut 200', function () {
    $document = PdfDocument::factory()->create([
        'title' => 'Document de test',
    ]);

    $response = $this->get(route('documents.show', $document->slug));

    $response->assertOk();
    $response->assertSee('Document de test');
});

it('affiche les documents pdf filtres par categorie avec un statut 200', function () {
    $pdfCategory = PdfCategory::factory()->create();
    PdfDocument::factory()->create(['pdf_category_id' => $pdfCategory->id]);

    $response = $this->get(route('documents.category', $pdfCategory->slug));

    $response->assertOk();
});