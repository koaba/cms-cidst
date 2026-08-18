<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PdfCategory;
use Illuminate\Http\Request;

class PdfCategoryController extends Controller
{
    public function index()
    {
        $categories = PdfCategory::withCount('documents')->latest()->paginate(10);

        return view('admin.pdf-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.pdf-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        PdfCategory::create($validated);

        return redirect()
            ->route('admin.pdf-categories.index')
            ->with('success', 'Catégorie créée avec succès.');
    }

    public function edit(PdfCategory $pdfCategory)
    {
        return view('admin.pdf-categories.edit', ['category' => $pdfCategory]);
    }

    public function update(Request $request, PdfCategory $pdfCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $pdfCategory->update($validated);

        return redirect()
            ->route('admin.pdf-categories.index')
            ->with('success', 'Catégorie modifiée avec succès.');
    }

    public function destroy(PdfCategory $pdfCategory)
    {
        $documentsCount = $pdfCategory->documents()->count();

        if ($documentsCount > 0) {
            return back()->with('error', "Impossible de supprimer cette catégorie : elle contient {$documentsCount} document(s). Supprimez-les d'abord ou déplacez-les.");
        }

        $pdfCategory->delete();

        return redirect()
            ->route('admin.pdf-categories.index')
            ->with('success', 'Catégorie supprimée avec succès.');
    }
}