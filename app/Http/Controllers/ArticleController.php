<?php
namespace App\Http\Controllers;
use App\Models\Article;
use App\Models\Category;
class ArticleController extends Controller
{
    public function index()
    {
        $articles = Article::where('is_published', true)
            ->with(['categories', 'user'])
            ->latest()
            ->paginate(9);
        return view('public.articles.index', compact('articles'));
    }
    public function show(Article $article)
    {
        if (!$article->is_published) {
            abort(404);
        }
        return view('public.articles.show', compact('article'));
    }
    public function byCategory(Category $category)
    {
        $articles = $category->articles()
            ->where('is_published', true)
            ->with(['categories', 'user'])
            ->latest()
            ->paginate(9);
        return view('public.articles.index', compact('articles', 'category'));
    }
}