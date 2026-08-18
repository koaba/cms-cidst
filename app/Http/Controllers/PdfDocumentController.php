<?php

namespace App\Http\Controllers;

use App\Models\PdfCategory;
use App\Models\PdfDocument;

class PdfDocumentController extends Controller
{
    public function index()
    {
        $documents = PdfDocument::with('category')
            ->latest()
            ->paginate(9);

        return view('public.pdf-documents.index', compact('documents'));
    }

    public function show(PdfDocument $pdfDocument)
    {
        $pdfDocument->load(['category', 'media']);

        return view('public.pdf-documents.show', ['document' => $pdfDocument]);
    }

    public function byCategory(PdfCategory $pdfCategory)
    {
        $documents = $pdfCategory->documents()
            ->with('category')
            ->latest()
            ->paginate(9);

        return view('public.pdf-documents.index', compact('documents', 'pdfCategory'));
    }
}