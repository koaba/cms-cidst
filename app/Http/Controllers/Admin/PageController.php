<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Media;
use App\Traits\HasOrphanMediaCleanup;
use App\Concerns\SavesSeoMeta;
use Illuminate\Http\Request;

class PageController extends Controller
{
    use HasOrphanMediaCleanup, SavesSeoMeta;

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
            'published_at' => 'required|date',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_published' => 'nullable|boolean',
            'seo' => 'nullable|array',
            'seo.meta_title' => 'nullable|string|max:60',
            'seo.meta_description' => 'nullable|string|max:320',
        ]);

        $pageData = collect($validated)->except(['image', 'seo'])->toArray();
        $pageData['user_id'] = auth()->id();
        $pageData['is_published'] = $request->boolean('is_published');

        $page = Page::create($pageData);
        $this->saveSeo($page, $validated);

        $file = $request->file('image');
        $path = $file->store('pages', 'public');
        $media = Media::create([
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);
        $page->media()->attach($media->id, ['order' => 0]);

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
            'published_at' => 'required|date',
            'image' => $page->media->isEmpty()
                ? 'required|image|mimes:jpg,jpeg,png,webp|max:2048'
                : 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_published' => 'nullable|boolean',
            'seo' => 'nullable|array',
            'seo.meta_title' => 'nullable|string|max:60',
            'seo.meta_description' => 'nullable|string|max:320',
        ]);

        $pageData = collect($validated)->except(['image', 'seo'])->toArray();
        $pageData['is_published'] = $request->boolean('is_published');

        $page->update($pageData);
        $this->saveSeo($page, $validated);

        if ($request->hasFile('image')) {
            $this->detachAndPruneOrphanMedia($page);

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
        $this->detachAndPruneOrphanMedia($page);

        $page->delete();

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'La page a été supprimée avec succès.');
    }
}