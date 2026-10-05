<?php

use App\Models\Page;
use App\Models\PdfCategory;
use App\Models\PdfDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
    Storage::fake('public');
});

it('cree un bloc pdf en selectionnant un document existant', function () {
    $page = Page::factory()->create();
    $category = PdfCategory::create(['name' => 'Une categorie']);
    $document = PdfDocument::create(['title' => 'Document existant', 'pdf_category_id' => $category->id]);

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'pdf', 'pdf_source' => 'existing', 'pdf_document_id' => $document->id]
    );

    $response->assertRedirect(route('admin.pages.blocks.index', $page));
    $block = $page->blocks()->whereNull('parent_id')->first();

    expect($block->data['pdf_document_id'])->toBe($document->id);
});

it('rejette un bloc pdf existant sans pdf_document_id', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'pdf', 'pdf_source' => 'existing']
    );

    $response->assertSessionHasErrors('pdf_document_id');
});

it('cree un nouveau document pdf a la volee et l\'attache au bloc', function () {
    $page = Page::factory()->create();

    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        [
            'type' => 'pdf',
            'pdf_source' => 'new',
            'pdf_title' => 'Notice technique',
            'pdfs' => [UploadedFile::fake()->create('notice.pdf', 100, 'application/pdf')],
        ]
    );

    $block = $page->blocks()->whereNull('parent_id')->first();
    $documentId = $block->data['pdf_document_id'];

    expect(PdfDocument::find($documentId)->title)->toBe('Notice technique');
    expect(PdfCategory::where('name', 'Non classé')->exists())->toBeTrue();
});

it('reutilise la categorie Non classe pour plusieurs blocs pdf crees a la volee', function () {
    $page = Page::factory()->create();

    foreach (['Doc A', 'Doc B'] as $title) {
        $this->actingAs($this->admin)->post(
            route('admin.pages.blocks.store', $page),
            [
                'type' => 'pdf',
                'pdf_source' => 'new',
                'pdf_title' => $title,
                'pdfs' => [UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')],
            ]
        );
    }

    expect(PdfCategory::where('name', 'Non classé')->count())->toBe(1);
});

it('rejette un nouveau document pdf sans titre', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        [
            'type' => 'pdf',
            'pdf_source' => 'new',
            'pdfs' => [UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')],
        ]
    );

    $response->assertSessionHasErrors('pdf_title');
});

it('rejette un nouveau document pdf sans fichier', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'pdf', 'pdf_source' => 'new', 'pdf_title' => 'Sans fichier']
    );

    $response->assertSessionHasErrors('pdfs');
});
