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
it('rejette un nouveau document pdf avec plus de fichiers que le maximum', function () {
    config(['media.max_pdfs' => 2]);
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        [
            'type' => 'pdf',
            'pdf_source' => 'new',
            'pdf_title' => 'Trop de fichiers',
            'pdfs' => [
                UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('b.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('c.pdf', 100, 'application/pdf'),
            ],
        ]
    );

    $response->assertSessionHasErrors('pdfs');
    expect($page->blocks()->whereNull('parent_id')->count())->toBe(0);
});

it('accepte un nouveau document pdf avec exactement le maximum de fichiers', function () {
    config(['media.max_pdfs' => 2]);
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        [
            'type' => 'pdf',
            'pdf_source' => 'new',
            'pdf_title' => 'A la limite',
            'pdfs' => [
                UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('b.pdf', 100, 'application/pdf'),
            ],
        ]
    );

    $response->assertSessionHasNoErrors();
    expect($page->blocks()->whereNull('parent_id')->count())->toBe(1);
});

it('rejette un pdf plus lourd que la taille maximale', function () {
    config(['media.max_pdf_upload_kb' => 200]);
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        [
            'type' => 'pdf',
            'pdf_source' => 'new',
            'pdf_title' => 'Trop lourd',
            'pdfs' => [UploadedFile::fake()->create('gros.pdf', 300, 'application/pdf')],
        ]
    );

    $response->assertSessionHasErrors('pdfs.0');
    expect($page->blocks()->whereNull('parent_id')->count())->toBe(0);
});

it('accepte un pdf exactement a la taille maximale', function () {
    config(['media.max_pdf_upload_kb' => 200]);
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        [
            'type' => 'pdf',
            'pdf_source' => 'new',
            'pdf_title' => 'Taille limite',
            'pdfs' => [UploadedFile::fake()->create('limite.pdf', 200, 'application/pdf')],
        ]
    );

    $response->assertSessionHasNoErrors();
    expect($page->blocks()->whereNull('parent_id')->count())->toBe(1);
});
