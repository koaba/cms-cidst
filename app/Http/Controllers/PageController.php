<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PdfDocument;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::publiclyVisible()->latest()->paginate(9);

        return view('public.pages.index', compact('pages'));
    }

    public function show(Page $page)
    {
        if (! $page->isPubliclyVisible()) {
            abort(404);
        }

        $page->loadBlockTree();

        $pdfDocuments = PdfDocument::with('pdfs')
            ->whereIn('id', $page->blocks->where('type', 'pdf')->pluck('data.pdf_document_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        return view('public.pages.show', compact('page', 'pdfDocuments'));
    }
}
