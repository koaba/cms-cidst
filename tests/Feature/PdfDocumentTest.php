<?php

use App\Models\Media;
use App\Models\PdfCategory;
use App\Models\PdfDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

function actingAsAdminPdfDoc(): User
{
    if (! Role::where('name', 'Super Admin')->exists()) {
        Role::create(['name' => 'Super Admin']);
    }
    $user = User::factory()->create();
    $user->assignRole('Super Admin');
    return $user;
}

it('cree une categorie de documents PDF', function () {
    $user = actingAsAdminPdfDoc();

    $response = $this->actingAs($user)->post('/admin/pdf-categories', [
        'name' => 'Science',
    ]);

    $response->assertRedirect(route('admin.pdf-categories.index'));
    $this->assertDatabaseHas('pdf_categories', ['name' => 'Science']);
});

it('refuse une categorie sans nom', function () {
    $user = actingAsAdminPdfDoc();

    $response = $this->actingAs($user)->post('/admin/pdf-categories', []);

    $response->assertSessionHasErrors(['name']);
    $this->assertDatabaseCount('pdf_categories', 0);
});

it('empeche de supprimer une categorie qui contient des documents', function () {
    Storage::fake('public');
    $user = actingAsAdminPdfDoc();

    $category = PdfCategory::create(['name' => 'Biologie']);
    $document = PdfDocument::create(['title' => 'Doc test', 'pdf_category_id' => $category->id]);
    $document->attachUploadedFiles([UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')], 'pdf-documents/pdfs');

    $response = $this->actingAs($user)->delete(route('admin.pdf-categories.destroy', $category));

    $response->assertRedirect();
    $this->assertDatabaseHas('pdf_categories', ['id' => $category->id]);
});

it('cree un document PDF avec upload direct', function () {
    Storage::fake('public');
    $user = actingAsAdminPdfDoc();
    $category = PdfCategory::create(['name' => 'Sciences sociales']);
    $pdf = UploadedFile::fake()->create('etude.pdf', 500, 'application/pdf');

    $response = $this->actingAs($user)->post('/admin/pdf-documents', [
        'title' => 'Étude sociale',
        'description' => 'Une étude complète',
        'pdf_category_id' => $category->id,
        'pdfs' => [$pdf],
    ]);

    $response->assertRedirect(route('admin.pdf-documents.index'));

    $document = PdfDocument::where('title', 'Étude sociale')->first();
    expect($document)->not->toBeNull();
    expect($document->pdfs)->toHaveCount(1);
    expect($document->category->id)->toBe($category->id);

    $media = $document->pdfs->first();
    Storage::disk('public')->assertExists($media->path);
});

it('cree un document PDF via selection depuis la mediatheque', function () {
    Storage::fake('public');
    $user = actingAsAdminPdfDoc();
    $category = PdfCategory::create(['name' => 'Chimie']);

    $file = UploadedFile::fake()->create('rapport.pdf', 300, 'application/pdf');
    $path = $file->store('pdf-documents/pdfs', 'public');
    $media = Media::create([
        'path' => $path,
        'original_name' => 'rapport.pdf',
        'mime_type' => 'application/pdf',
        'size' => 300,
    ]);

    $response = $this->actingAs($user)->post('/admin/pdf-documents', [
        'title' => 'Rapport chimie',
        'pdf_category_id' => $category->id,
        'existing_media' => [$media->id],
    ]);

    $response->assertRedirect(route('admin.pdf-documents.index'));

    $document = PdfDocument::where('title', 'Rapport chimie')->first();
    expect($document->pdfs()->where('media.id', $media->id)->exists())->toBeTrue();
});

it('refuse la creation d\'un document sans aucun PDF', function () {
    $user = actingAsAdminPdfDoc();
    $category = PdfCategory::create(['name' => 'Physique']);

    $response = $this->actingAs($user)->post('/admin/pdf-documents', [
        'title' => 'Document vide',
        'pdf_category_id' => $category->id,
    ]);

    $response->assertSessionHasErrors('pdfs');
    $this->assertDatabaseMissing('pdf_documents', ['title' => 'Document vide']);
});

it('refuse la creation d\'un document sans categorie', function () {
    Storage::fake('public');
    $user = actingAsAdminPdfDoc();
    $pdf = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

    $response = $this->actingAs($user)->post('/admin/pdf-documents', [
        'title' => 'Document sans categorie',
        'pdfs' => [$pdf],
    ]);

    $response->assertSessionHasErrors('pdf_category_id');
    $this->assertDatabaseMissing('pdf_documents', ['title' => 'Document sans categorie']);
});

it('refuse plus de 10 documents PDF sur un document', function () {
    Storage::fake('public');
    $user = actingAsAdminPdfDoc();
    $category = PdfCategory::create(['name' => 'Astronomie']);

    $files = collect(range(1, 11))->map(
        fn ($i) => UploadedFile::fake()->create("doc{$i}.pdf", 100, 'application/pdf')
    )->all();

    $response = $this->actingAs($user)->post('/admin/pdf-documents', [
        'title' => 'Trop de PDF',
        'pdf_category_id' => $category->id,
        'pdfs' => $files,
    ]);

    $response->assertSessionHasErrors('pdfs');
    $this->assertDatabaseMissing('pdf_documents', ['title' => 'Trop de PDF']);
});

it('empeche de supprimer le PDF d\'un autre document via delete_pdfs', function () {
    Storage::fake('public');
    $user = actingAsAdminPdfDoc();
    $category = PdfCategory::create(['name' => 'Geologie']);

    $documentA = PdfDocument::create(['title' => 'Document A', 'pdf_category_id' => $category->id]);
    $documentA->attachUploadedFiles([UploadedFile::fake()->create('a.pdf', 200, 'application/pdf')], 'pdf-documents/pdfs');
    $mediaA = $documentA->pdfs->first();

    $documentB = PdfDocument::create(['title' => 'Document B', 'pdf_category_id' => $category->id]);
    $documentB->attachUploadedFiles([UploadedFile::fake()->create('b.pdf', 200, 'application/pdf')], 'pdf-documents/pdfs');

    $response = $this->actingAs($user)->put(route('admin.pdf-documents.update', $documentB), [
        'title' => 'Document B',
        'pdf_category_id' => $category->id,
        'delete_pdfs' => [$mediaA->id],
    ]);

    $response->assertSessionHasErrors('delete_pdfs.0');
    expect($documentA->pdfs()->where('media.id', $mediaA->id)->exists())->toBeTrue();
});

it('met a jour un document en supprimant un PDF existant et en ajoutant un nouveau', function () {
    Storage::fake('public');
    $user = actingAsAdminPdfDoc();
    $category = PdfCategory::create(['name' => 'Informatique']);

    $document = PdfDocument::create(['title' => 'Document edit', 'pdf_category_id' => $category->id]);
    $document->attachUploadedFiles([UploadedFile::fake()->create('old.pdf', 150, 'application/pdf')], 'pdf-documents/pdfs');
    $oldMedia = $document->pdfs->first();

    $newFile = UploadedFile::fake()->create('new.pdf', 150, 'application/pdf');

    $response = $this->actingAs($user)->put(route('admin.pdf-documents.update', $document), [
        'title' => 'Document edit',
        'pdf_category_id' => $category->id,
        'delete_pdfs' => [$oldMedia->id],
        'pdfs' => [$newFile],
    ]);

    $response->assertRedirect(route('admin.pdf-documents.index'));

    $document->refresh();
    expect($document->pdfs()->where('media.id', $oldMedia->id)->exists())->toBeFalse();
    expect($document->pdfs)->toHaveCount(1);
    expect($document->pdfs->first()->original_name)->toBe('new.pdf');
});