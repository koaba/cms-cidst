<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageBlock;
use App\Services\PageBuilder\BlockFieldCatalog;
use App\Services\PageBuilder\BlockSyncService;
use App\Services\PageBuilder\StaleBuilderException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Page Builder : seconde interface d'edition des blocs racine d'une page. */
class PageBuilderController extends Controller
{
    public function show(Page $page, BlockSyncService $sync): View
    {
        $labels = config('page_blocks.types');

        $blocks = $page->blocks()
            ->whereNull('parent_id')
            ->withCount('children')
            ->with('media')
            ->orderBy('order')
            ->get()
            ->map(function (PageBlock $block) use ($page, $labels) {
                $editable = in_array($block->type, BlockSyncService::EDITABLE_TYPES, true);

                $base = [
                    'id' => $block->id,
                    'type' => $block->type,
                    'label' => $labels[$block->type] ?? $block->type,
                    'locked' => ! $editable,
                ];

                // Un bloc verrouille n'expose jamais ses donnees au builder.
                return $base + ($editable
                    ? ['data' => $block->data ?? []] + $this->mediaPayload($block)
                    : [
                        'childrenCount' => $block->children_count,
                        'editUrl' => route('admin.pages.blocks.edit', [$page, $block->id]),
                    ]);
            })
            ->all();

        $types = collect(BlockFieldCatalog::all())
            ->map(fn (array $definition, string $type) => $definition + ['label' => $labels[$type] ?? $type])
            ->all();

        return view('admin.pages.builder', [
            'page' => $page,
            'builder' => [
                'saveUrl' => route('admin.pages.builder.save', $page),
                'classicUrl' => route('admin.pages.blocks.index', $page),
                'types' => $types,
                'blocks' => $blocks,
                'version' => $sync->version($page),
            ],
        ]);
    }

    /** Image actuelle des blocs a image unique (null si aucune). Aucune cle pour les autres types. */
    private function mediaPayload(PageBlock $block): array
    {
        if (! in_array($block->type, BlockSyncService::MEDIA_TYPES, true)) {
            return [];
        }

        $media = $block->media->first();

        return ['media' => $media ? [
            'id' => $media->id,
            'url' => $media->thumbnail_url,
            'name' => $media->original_name,
        ] : null];
    }

    public function save(Request $request, Page $page, BlockSyncService $sync): JsonResponse
    {
        $payload = $request->validate([
            'blocks' => ['present', 'array', 'max:200'],
            'blocks.*' => ['array'],
            'blocks.*.id' => ['nullable', 'integer'],
            'blocks.*.type' => ['required', 'string'],
            'blocks.*.data' => ['nullable', 'array'],
            'blocks.*.media_id' => ['nullable', 'integer'],
            'version' => ['nullable', 'string'],
        ]);

        try {
            $blocks = $sync->sync($page, $payload['blocks'], $payload['version'] ?? null);
        } catch (StaleBuilderException $e) {
            return response()->json(['success' => false, 'code' => 'stale', 'message' => $e->getMessage()], 409);
        }

        return response()->json([
            'success' => true,
            'blocks' => $blocks,
            'version' => $sync->version($page),
        ]);
    }
}
