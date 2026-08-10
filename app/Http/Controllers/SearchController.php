<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Page;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $query = trim($validated['q'] ?? '');

        $articles = collect();
        $pages = collect();

        if ($query !== '') {
            $articles = Article::query()
                ->where('is_published', true)
                ->where('published_at', '<=', now())
                ->where('title', 'like', "%{$query}%")
                ->latest('published_at')
                ->limit(20)
                ->get();

            $pages = Page::query()
                ->where('is_published', true)
                ->where('title', 'like', "%{$query}%")
                ->limit(20)
                ->get();
        }

        return view('public.search.index', [
            'query' => $query,
            'articles' => $articles,
            'pages' => $pages,
        ]);
    }
}