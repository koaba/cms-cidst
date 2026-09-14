<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageBlock;
use Illuminate\Http\Request;

class PageBlockController extends Controller
{
    public function index(Page $page)
    {
        $blocks = $page->blocks;
        $types = config('page_blocks.types');

        return view('admin.pages.blocks.index', compact('page', 'blocks', 'types'));
    }

    public function create(Page $page, string $type)
    {
        if (! array_key_exists($type, config('page_blocks.types'))) {
            abort(404);
        }

        return view('admin.pages.blocks.create', compact('page', 'type'));
    }

    public function store(Request $request, Page $page)
    {
        $type = $request->input('type');

        if (! array_key_exists($type, config('page_blocks.types'))) {
            abort(404);
        }

        $data = $this->validateForType($request, $type, isCreate: true);
        $data = $this->stripMediaFields($data);

        $block = $page->blocks()->create([
            'type' => $type,
            'data' => $data,
            'order' => $page->blocks()->max('order') + 1,
        ]);

        $this->handleMedia($request, $block, $type);

        return redirect()
            ->route('admin.pages.blocks.index', $page)
            ->with('success', 'Le bloc a été ajouté avec succès.');
    }

    public function edit(Page $page, int $blockId)
    {
        $block = $page->blocks()->findOrFail($blockId);

        return view('admin.pages.blocks.edit', compact('page', 'block'));
    }

    public function update(Request $request, Page $page, int $blockId)
    {
        $block = $page->blocks()->findOrFail($blockId);

        $data = $this->validateForType($request, $block->type, isCreate: false);
        $data = $this->stripMediaFields($data);

        $block->update(['data' => $data]);

        $this->handleMedia($request, $block, $block->type);

        return redirect()
            ->route('admin.pages.blocks.index', $page)
            ->with('success', 'Le bloc a été modifié avec succès.');
    }
    public function destroy(Page $page, int $blockId)
    {
        $block = $page->blocks()->findOrFail($blockId);

        $block->detachAndPruneOrphanMedia($block);
        $block->delete();

        return redirect()
            ->route('admin.pages.blocks.index', $page)
            ->with('success', 'Le bloc a été supprimé avec succès.');
    }

    public function reorder(Request $request, Page $page)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:page_blocks,id',
        ]);

        foreach ($validated['order'] as $index => $blockId) {
            PageBlock::where('id', $blockId)
                ->where('page_id', $page->id)
                ->update(['order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    private function validateForType(Request $request, string $type, bool $isCreate = false): array
    {
        return match ($type) {
            'texte' => $request->validate([
                'title' => 'nullable|string|max:255',
                'content' => 'required|string',
            ]),
            'separateur' => $request->validate([
                'style' => 'nullable|in:fin,epais',
            ]),
            'citation' => $request->validate([
                'content' => 'required|string',
                'author' => 'nullable|string|max:255',
            ]),
            'bouton' => $request->validate([
                'label' => 'required|string|max:100',
                'url' => 'required|string|max:255',
                'style' => 'nullable|in:primaire,secondaire,outline',
                'new_tab' => 'nullable|boolean',
            ]),
            'image' => $request->validate([
                'image' => ($isCreate ? 'required' : 'nullable') . '|image|max:5120',
                'alt' => 'nullable|string|max:255',
                'caption' => 'nullable|string|max:255',
                'delete_image' => 'nullable|boolean',
            ]),
            'video' => $request->validate([
                'title' => 'nullable|string|max:255',
                'source_type' => 'required|in:upload,url',
                'url' => 'required_if:source_type,url|nullable|string|max:255',
                'video_file' => 'required_if:source_type,upload|nullable|file|mimes:mp4,webm|max:15360',
                'delete_video' => 'nullable|boolean',
            ]),
            'section_fond' => $request->validate([
                'title' => 'nullable|string|max:255',
                'text' => 'required|string',
                'bg_color' => 'nullable|in:gray,blue,dark',
                'button_label' => 'nullable|string|max:100',
                'button_url' => 'nullable|string|max:255',
                'button_new_tab' => 'nullable|boolean',
            ]),
            'galerie' => $request->validate([
                'layout' => 'required|in:grid,carousel',
                'images' => ($isCreate ? 'required' : 'nullable') . '|array|min:1|max:20',
                'images.*' => 'image|max:5120',
                'images_alt' => 'nullable|array',
                'images_alt.*' => 'nullable|string|max:255',
                'images_caption' => 'nullable|array',
                'images_caption.*' => 'nullable|string|max:255',
                'delete_media' => 'nullable|array',
                'delete_media.*' => 'integer',
            ]),
            default => abort(404, "Type de bloc « {$type} » non implémenté."),
        };
    }

    /**
     * Retire du tableau de données validées tout ce qui concerne les
     * fichiers/médias : ces champs sont traités par handleMedia() et ne
     * doivent jamais être stockés dans la colonne JSON `data` du bloc.
     */
    private function stripMediaFields(array $data): array
    {
        unset(
            $data['image'], $data['delete_image'],
            $data['video_file'], $data['delete_video'],
            $data['images'], $data['images_alt'], $data['images_caption'], $data['delete_media'],
        );

        return $data;
    }

        private function handleMedia(Request $request, PageBlock $block, string $type): void
    {
        if ($type === 'image') {
            if ($request->boolean('delete_image')) {
                $block->detachOwnedMedia($block->media()->pluck('media.id')->all());
            }

            if ($request->hasFile('image')) {
                $block->detachOwnedMedia($block->media()->pluck('media.id')->all());

                $file = $request->file('image');
                $path = $file->store('pages', 'public');
                $media = Media::create([
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'type' => 'image',
                ]);
                $block->media()->attach($media->id, ['order' => 0]);
            }
        }

        if ($type === 'video') {
            if ($request->boolean('delete_video')) {
                $block->detachOwnedMedia($block->media()->pluck('media.id')->all());
            }

            if (($block->data['source_type'] ?? null) === 'upload' && $request->hasFile('video_file')) {
                $block->detachOwnedMedia($block->media()->pluck('media.id')->all());

                $file = $request->file('video_file');
                $path = $file->store('pages', 'public');
                $media = Media::create([
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'type' => 'video',
                ]);
                $block->media()->attach($media->id, ['order' => 0]);
            }
        }

        if ($type === 'galerie') {
            if ($request->filled('delete_media')) {
                $block->detachOwnedMedia($request->input('delete_media'));
            }

            if ($request->hasFile('images')) {
                $startOrder = $block->media()->count();
                $alts = $request->input('images_alt', []);
                $captions = $request->input('images_caption', []);

                foreach ($request->file('images') as $index => $file) {
                    $path = $file->store('pages', 'public');
                    $media = Media::create([
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
                        'size' => $file->getSize(),
                        'type' => 'image',
                    ]);

                    $block->media()->attach($media->id, [
                        'order' => $startOrder + $index,
                        'alt' => $alts[$index] ?? null,
                        'caption' => $captions[$index] ?? null,
                    ]);
                }
            }
        }
    }
}