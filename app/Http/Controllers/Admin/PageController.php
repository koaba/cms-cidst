<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\SavesSeoMeta;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\Page;
use App\Services\MediaUploader;
use App\Traits\HasOrphanMediaCleanup;

class PageController extends Controller
{
    use HasOrphanMediaCleanup, SavesSeoMeta;

    public function __construct(private readonly MediaUploader $uploader) {}

    public function index()
    {
        $pages = Page::latest()->paginate(10);

        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.pages.create');
    }

    public function store(StorePageRequest $request)
    {
        $validated = $request->validated();

        $pageData = collect($validated)->except(['image', 'seo'])->toArray();
        $pageData['user_id'] = auth()->id();
        $pageData['is_published'] = $request->boolean('is_published');

        $page = Page::create($pageData);
        $this->saveSeo($page, $validated);

        if ($request->hasFile('image')) {
            $media = $this->uploader->fromUpload($request->file('image'), 'pages');
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

    public function update(UpdatePageRequest $request, Page $page)
    {
        $validated = $request->validated();

        $pageData = collect($validated)->except(['image', 'seo'])->toArray();
        $pageData['is_published'] = $request->boolean('is_published');

        if (blank($pageData['slug'] ?? null)) {
            unset($pageData['slug']);
        }

        $page->update($pageData);
        $this->saveSeo($page, $validated);

        if ($request->hasFile('image')) {
            $this->detachAndPruneOrphanMedia($page);

            $media = $this->uploader->fromUpload($request->file('image'), 'pages');
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
