<?php 

namespace App\Http\Controllers\Admin;

use App\Models\Article; 
use App\Models\Media;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Category; 

class ArticleController extends Controller
{
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

    public function store(Request $request)
    {
      $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_published' => 'nullable|boolean',
            'published_at' => 'nullable|date',
            'gallery_display' => 'nullable|in:grid,slideshow',
            'image' => 'nullable|image|max:2048', 
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,webp|max:4096',
            'delete_images' => 'nullable|array',
            'delete_images.*' => 'integer|exists:media,id',
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:categories,id',
        ]);
        $validated['user_id'] = auth()->id();
        $validated['published_at'] = $validated['published_at'] ?? now();

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('articles', 'public');
        }

        $article = Article::create($validated);

        $article->categories()->sync($validated['categories'] ?? []);

        if ($request->hasFile('images')) { 
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('articles/gallery', 'public');
                $media = Media::create([
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
                $article->media()->attach($media->id, ['order' => $index]); 
            }
        } 

        return redirect()->route('admin.articles.index')->with('success', 'Article créé avec succès.');
    }
 
    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        $article = Article::findOrFail($id); 
        $categories = Category::all();

        return view('admin.articles.edit', compact('article', 'categories'));
    }

    public function update(Request $request, string $id)
    {
        $article = Article::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_published' => 'nullable|boolean',
            'published_at' => 'nullable|date',
            'gallery_display' => 'nullable|in:grid,slideshow',
            'image' => 'nullable|image|max:2048',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,webp|max:4096',
            'delete_images' => 'nullable|array',
            'delete_images.*' => 'integer|exists:media,id',
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:categories,id',
        ]);

        $validated['published_at'] = $validated['published_at'] ?? $article->published_at ?? now();

        if ($request->hasFile('image')) {
            if ($article->image) {
                Storage::disk('public')->delete($article->image);
            }
            $validated['image'] = $request->file('image')->store('articles', 'public');
        }

        $article->update($validated);

        $article->categories()->sync($validated['categories'] ?? []);

        if ($request->filled('delete_images')) {
            $article->media()->detach($request->input('delete_images'));
        } 

        if ($request->hasFile('images')) {
            $existingCount = $article->media()->count();
            $maxNew = max(0, 15 - $existingCount);
            foreach (array_slice($request->file('images'), 0, $maxNew) as $index => $file) {
                $path = $file->store('articles/gallery', 'public');
                $media = Media::create([
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
                $article->media()->attach($media->id, ['order' => $existingCount + $index]);
            }
        }

        return redirect()->route('admin.articles.index')->with('success', 'Article modifié avec succès.');
    }

 public function destroy(string $id)
{
    $article = Article::findOrFail($id);

    foreach ($article->media as $media) {
        // Supprime le fichier définitivement seulement s'il n'est pas partagé ailleurs
        if ($media->mediables()->count() <= 1) {
            $media->delete();
        }
    }

    $article->media()->detach();
    $article->delete();

    return redirect()->route('admin.articles.index')->with('success', 'Article supprimé avec succès.');
}
}