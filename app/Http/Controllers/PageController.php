<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Support\PublicPageCache;

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

        $pdfDocuments = PublicPageCache::hydrate($page);

        return view('public.pages.show', compact('page', 'pdfDocuments'));
    }
}
