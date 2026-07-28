<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Media;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::latest()->paginate(10);

        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.pages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_published' => 'nullable|boolean',
        ]);

        $pageData = collect($validated)->except('image')->toArray();
        $pageData['user_id'] = auth()->id();
        $pageData['is_published'] = $request->boolean('is_published');

        $page = Page::create($pageData);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('pages', 'public');
            $media = Media::create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
            $page->media()->attach($media->id, ['order' => 0]);
        }

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'La page a été créée avec succès.');
    }

    public function show(Page $page)
    {
        return view('admin.pages.show', compact('page'));
    }

    public function edit(Page $page)
    {
        return view('admin.pages.edit', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_published' => 'nullable|boolean',
        ]);

        $pageData = collect($validated)->except('image')->toArray();
        $pageData['is_published'] = $request->boolean('is_published');

        $page->update($pageData);

        if ($request->hasFile('image')) {
            $page->media()->detach();
            $file = $request->file('image');
            $path = $file->store('pages', 'public');
            $media = Media::create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
            $page->media()->attach($media->id, ['order' => 0]);
        }

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'La page a été modifiée avec succès.');
    }

    public function destroy(Page $page)
    {
        $page->media()->detach();
        $page->delete();

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'La page a été supprimée avec succès.');
    }
}