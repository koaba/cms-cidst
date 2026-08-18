<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\PdfCategory;
use App\Models\PdfDocument;
use App\Traits\HasOrphanMediaCleanup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PdfDocumentController extends Controller
{
    use HasOrphanMediaCleanup;

    private const MAX_PDFS = 10;

    public function index()
    {
        $documents = PdfDocument::with('category')->latest()->paginate(10);

        return view('admin.pdf-documents.index', compact('documents'));
    }

    public function create()
    {
        $categories = PdfCategory::orderBy('name')->get();

        return view('admin.pdf-documents.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateDocument($request);

        $document = PdfDocument::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'pdf_category_id' => $validated['pdf_category_id'],
        ]);

        $this->syncPdfs($request, $document);

        return redirect()
            ->route('admin.pdf-documents.index')
            ->with('success', 'Document créé avec succès.');
    }

    public function edit(PdfDocument $pdfDocument)
    {
        $pdfDocument->load('media');
        $categories = PdfCategory::orderBy('name')->get();

        return view('admin.pdf-documents.edit', ['document' => $pdfDocument, 'categories' => $categories]);
    }

    public function update(Request $request, PdfDocument $pdfDocument)
    {
        $validated = $this->validateDocument($request, $pdfDocument);

        $pdfDocument->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'pdf_category_id' => $validated['pdf_category_id'],
        ]);

        $this->syncPdfs($request, $pdfDocument, isUpdate: true);

        return redirect()
            ->route('admin.pdf-documents.index')
            ->with('success', 'Document modifié avec succès.');
    }

    public function destroy(PdfDocument $pdfDocument)
    {
        $this->detachAndPruneOrphanMedia($pdfDocument);

        $pdfDocument->delete();

        return redirect()
            ->route('admin.pdf-documents.index')
            ->with('success', 'Document supprimé avec succès.');
    }

    /* ------------------------------------------------------------------ */

    private function validateDocument(Request $request, ?PdfDocument $document = null): array
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'pdf_category_id' => 'required|exists:pdf_categories,id',
            'pdfs' => 'nullable|array|max:' . self::MAX_PDFS,
            'pdfs.*' => 'file|mimes:pdf|max:10240',
            'existing_media' => 'nullable|array',
            'existing_media.*' => 'integer|exists:media,id',
            'delete_pdfs' => 'nullable|array',
            'delete_pdfs.*' => [
                'integer',
                $document
                    ? Rule::exists('mediables', 'media_id')
                        ->where('mediable_type', PdfDocument::class)
                        ->where('mediable_id', $document->id)
                    : 'exists:media,id',
            ],
        ]);

        $validator->after(function ($validator) use ($request, $document) {
            $currentCount = $document ? $document->pdfs()->count() : 0;
            $toDelete = count($request->input('delete_pdfs', []));
            $incoming = count($request->file('pdfs', [])) + count($request->input('existing_media', []));
            if (($currentCount - $toDelete + $incoming) > self::MAX_PDFS) {
                $validator->errors()->add('pdfs', 'Un document ne peut pas avoir plus de ' . self::MAX_PDFS . ' fichiers PDF.');
            }

            if (! $document && $incoming === 0) {
                $validator->errors()->add('pdfs', 'Un document doit avoir au moins un fichier PDF (upload direct ou sélection depuis la médiathèque).');
            }
        });

        return $validator->validate();
    }

    private function syncPdfs(Request $request, PdfDocument $document, bool $isUpdate = false): void
    {
        if ($isUpdate && $request->filled('delete_pdfs')) {
            $document->detachOwnedMedia($request->input('delete_pdfs'));
        }

        if ($request->filled('existing_media')) {
            $document->attachExistingMedia($request->input('existing_media'));
        }

        if ($request->hasFile('pdfs')) {
            $document->attachUploadedFiles($request->file('pdfs'), 'pdf-documents/pdfs');
        }
    }
}