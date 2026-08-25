<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreArticleRequest;
use App\Http\Requests\Admin\UpdateArticleRequest;
use App\Models\Article;
use App\Models\Category;
use App\Services\MediaSyncService;
use App\Traits\HasOrphanMediaCleanup;
use App\Concerns\SavesSeoMeta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ArticleController extends Controller
{
    use HasOrphanMediaCleanup, SavesSeoMeta;

    public function __construct(private MediaSyncService $mediaSync)
    {
    }

    public function index()
    {
        $articles = Article::latest('published_at')->paginate(10);

        return view('admin.articles.index', compact('articles'));
    }

    public function create()
    {
        $categories = Category::all();

        return view('admin.articles.create', compact('categories'));
    }

    public function store(StoreArticleRequest $request)
    {
        $validated = $request->validated();

        $article = DB::transaction(function () use ($request, $validated) {
            $validated['user_id'] = auth()->id();
            $validated['published_at'] = $validated['published_at'] ?? now();

            if ($path = $this->mediaSync->syncCoverImage($request, null)) {
                $validated['image'] = $path;
            }

            $article = Article::create($validated);
            $this->saveSeo($article, $validated);
            $article->categories()->sync($validated['categories'] ?? []);

            $this->mediaSync->syncGallery($request, $article);
            $this->mediaSync->syncPdfs($request, $article);
            $this->mediaSync->syncDiaporamas($request, $article);
            $this->mediaSync->syncVideos($request, $article);

            return $article;
        });

        return redirect()->route('admin.articles.index')->with('success', 'Article créé avec succès.');
    }

    public function show(Article $article)
    {
        //
    }

    public function edit(Article $article)
    {
        $article->load(['diaporamas.media', 'videos', 'media']);
        $categories = Category::all();

        return view('admin.articles.edit', compact('article', 'categories'));
    }

    public function update(UpdateArticleRequest $request, Article $article)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated, $article) {
            $validated['published_at'] = $validated['published_at'] ?? $article->published_at ?? now();

            if ($path = $this->mediaSync->syncCoverImage($request, $article)) {
                $validated['image'] = $path;
            }

            $article->update($validated);
            $this->saveSeo($article, $validated);
            $article->categories()->sync($validated['categories'] ?? []);

            $this->mediaSync->syncGallery($request, $article, isUpdate: true);
            $this->mediaSync->syncPdfs($request, $article, isUpdate: true);
            $this->mediaSync->syncDiaporamas($request, $article, isUpdate: true);
            $this->mediaSync->syncVideos($request, $article, isUpdate: true);
        });

        return redirect()->route('admin.articles.index')->with('success', 'Article modifié avec succès.');
    }

    public function destroy(Article $article)
    {
        $article->load(['diaporamas', 'videos']);

        DB::transaction(function () use ($article) {
            // Galerie simple
            $this->detachAndPruneOrphanMedia($article);

            // Diaporamas : nettoie les médias de chaque diaporama, puis le diaporama lui-même
            foreach ($article->diaporamas as $diaporama) {
                $this->detachAndPruneOrphanMedia($diaporama);
                $diaporama->delete();
            }

            // Vidéos : le model event `deleting` sur Video supprime le fichier si uploadé
            foreach ($article->videos as $video) {
                $video->delete();
            }

            if ($article->image) {
                Storage::disk('public')->delete($article->image);
            }

            $article->delete();
        });

        return redirect()->route('admin.articles.index')->with('success', 'Article supprimé avec succès.');
    }
}