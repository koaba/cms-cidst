<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Page;
use App\Support\Like;
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
            $articles = Like::contains(Article::query()->publiclyVisible(), 'title', $query)
                ->latest('published_at')
                ->limit(20)
                ->get();

            $pages = Like::contains(Page::query()->publiclyVisible(), 'title', $query)
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
