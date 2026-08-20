<?php
namespace App\Http\Controllers;
use App\Models\PdfCategory;
use App\Models\PdfDocument;
use App\Models\SiteSetting;

class PdfDocumentController extends Controller
{
    public function index()
    {
        $documents = PdfDocument::with('category', 'pdfs')
            ->latest()
            ->paginate(9);
        $settings = SiteSetting::current();
        return view('public.pdf-documents.index', compact('documents', 'settings'));
    }
    public function show(PdfDocument $pdfDocument)
    {
        $pdfDocument->load(['category', 'media']);
        return view('public.pdf-documents.show', ['document' => $pdfDocument]);
    }
    public function byCategory(PdfCategory $pdfCategory)
    {
        $documents = $pdfCategory->documents()
            ->with('category', 'pdfs')
            ->latest()
            ->paginate(9);
        $settings = SiteSetting::current();
        return view('public.pdf-documents.index', compact('documents', 'pdfCategory', 'settings'));
    }
}