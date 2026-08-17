<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Http\Controllers\Controller;
use App\Traits\HasOrphanMediaCleanup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    use HasOrphanMediaCleanup;

    private const MAX_PDFS = 10;

    public function index()
    {
        $categories = Category::withCount('articles')->latest()->paginate(10);
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateCategory($request);

        $category = Category::create(['name' => $validated['name']]);

        $this->syncPdfs($request, $category);

        return redirect()->route('admin.categories.index')->with('success', 'Catégorie créée avec succès.');
    }

    public function edit(Category $category)
    {
        $category->load('media');
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $this->validateCategory($request, $category);

        $category->update(['name' => $validated['name']]);

        $this->syncPdfs($request, $category, isUpdate: true);

        return redirect()->route('admin.categories.index')->with('success', 'Catégorie modifiée avec succès.');
    }

    public function destroy(Category $category)
    {
        $articlesCount = $category->articles()->count();

        if ($articlesCount > 0) {
            return back()->with('error', "Impossible de supprimer cette catégorie : elle est utilisée par {$articlesCount} article(s). Retirez-la de ces articles avant de la supprimer.");
        }

        $this->detachAndPruneOrphanMedia($category);

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Catégorie supprimée avec succès.');
    }

    /* ------------------------------------------------------------------ */

    private function validateCategory(Request $request, ?Category $category = null): array
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'pdfs' => 'nullable|array|max:' . self::MAX_PDFS,
            'pdfs.*' => 'file|mimes:pdf|max:10240',
            'existing_media' => 'nullable|array',
            'existing_media.*' => 'integer|exists:media,id',
            'delete_pdfs' => 'nullable|array',
            'delete_pdfs.*' => [
                'integer',
                $category
                    ? Rule::exists('mediables', 'media_id')
                        ->where('mediable_type', Category::class)
                        ->where('mediable_id', $category->id)
                    : 'exists:media,id',
            ],
        ]);

        $validator->after(function ($validator) use ($request, $category) {
            $currentCount = $category ? $category->media()->count() : 0;
            $toDelete = count($request->input('delete_pdfs', []));
            $incoming = count($request->file('pdfs', [])) + count($request->input('existing_media', []));
            if (($currentCount - $toDelete + $incoming) > self::MAX_PDFS) {
                $validator->errors()->add('pdfs', 'Une catégorie ne peut pas avoir plus de ' . self::MAX_PDFS . ' documents PDF.');
            }
        });

        return $validator->validate();
    }

    private function syncPdfs(Request $request, Category $category, bool $isUpdate = false): void
    {
        if ($isUpdate && $request->filled('delete_pdfs')) {
            $category->detachOwnedMedia($request->input('delete_pdfs'));
        }

        if ($request->filled('existing_media')) {
            $category->attachExistingMedia($request->input('existing_media'));
        }

        if ($request->hasFile('pdfs')) {
            $category->attachUploadedFiles($request->file('pdfs'), 'categories/pdfs');
        }
    }
}