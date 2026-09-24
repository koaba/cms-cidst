<?php

namespace App\Http\Controllers;

use App\Models\Page;

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

        return view('public.pages.show', compact('page'));
    }
}