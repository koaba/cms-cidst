<?php
namespace App\Http\Controllers;
use App\Models\Article;
use App\Concerns\SavesSeoMeta;
use App\Models\Category;
class ArticleController extends Controller
{
    use SavesSeoMeta;
    public function index()
    {
        $articles = Article::where('is_published', true)
            ->where('published_at', '<=', now())
            ->with(['categories', 'user'])
            ->orderByDesc('published_at')
            ->paginate(9);
        return view('public.articles.index', compact('articles'));
    }
   public function show(Article $article)
    {
        if (!$article->is_published || $article->published_at > now()) {
            abort(404);
        }

        $article->load(['categories', 'user', 'media', 'diaporamas.media', 'videos']);

        return view('public.articles.show', compact('article'));
    }
    public function byCategory(Category $category)
    {
        $articles = $category->articles()
            ->where('is_published', true)
            ->where('published_at', '<=', now())
            ->with(['categories', 'user'])
            ->orderByDesc('published_at')
            ->paginate(9);
        return view('public.articles.index', compact('articles', 'category'));
    }
}