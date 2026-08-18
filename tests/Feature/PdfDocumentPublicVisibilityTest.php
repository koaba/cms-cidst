<?php

use App\Models\PdfCategory;
use App\Models\PdfDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('affiche la liste des documents PDF publics', function () {
    Storage::fake('public');
    $category = PdfCategory::create(['name' => 'Science']);
    $document = PdfDocument::create(['title' => 'Document public', 'pdf_category_id' => $category->id]);
    $document->attachUploadedFiles([UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')], 'pdf-documents/pdfs');

    $response = $this->get(route('documents.index'));

    $response->assertOk();
    $response->assertSee('Document public');
});

it('affiche un etat vide propre quand aucun document n\'existe', function () {
    $response = $this->get(route('documents.index'));

    $response->assertOk();
    $response->assertSee('Aucun document pour le moment.');
});

it('filtre les documents par categorie', function () {
    Storage::fake('public');
    $categoryA = PdfCategory::create(['name' => 'Biologie']);
    $categoryB = PdfCategory::create(['name' => 'Chimie']);

    $documentA = PdfDocument::create(['title' => 'Document biologie', 'pdf_category_id' => $categoryA->id]);
    $documentA->attachUploadedFiles([UploadedFile::fake()->create('a.pdf', 100, 'application/pdf')], 'pdf-documents/pdfs');

    $documentB = PdfDocument::create(['title' => 'Document chimie', 'pdf_category_id' => $categoryB->id]);
    $documentB->attachUploadedFiles([UploadedFile::fake()->create('b.pdf', 100, 'application/pdf')], 'pdf-documents/pdfs');

    $response = $this->get(route('documents.category', $categoryA));

    $response->assertOk();
    $response->assertSee('Document biologie');
    $response->assertDontSee('Document chimie');
});

it('affiche la fiche d\'un document avec ses fichiers PDF', function () {
    Storage::fake('public');
    $category = PdfCategory::create(['name' => 'Physique']);
    $document = PdfDocument::create([
        'title' => 'Etude physique',
        'description' => 'Une description detaillee',
        'pdf_category_id' => $category->id,
    ]);
    $document->attachUploadedFiles([UploadedFile::fake()->create('etude.pdf', 100, 'application/pdf')], 'pdf-documents/pdfs');

    $response = $this->get(route('documents.show', $document));

    $response->assertOk();
    $response->assertSee('Etude physique');
    $response->assertSee('Une description detaillee');
    $response->assertSee('Télécharger');
});

it('affiche un etat vide sur la fiche quand le document n\'a aucun fichier', function () {
    $category = PdfCategory::create(['name' => 'Mathematiques']);
    $document = PdfDocument::create(['title' => 'Document sans fichier', 'pdf_category_id' => $category->id]);

    $response = $this->get(route('documents.show', $document));

    $response->assertOk();
    $response->assertSee('Aucun fichier disponible pour le moment.');
});