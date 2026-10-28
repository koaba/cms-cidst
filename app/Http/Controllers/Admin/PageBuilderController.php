<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageBlock;
use App\Services\PageBuilder\BlockFieldCatalog;
use App\Services\PageBuilder\BlockSyncService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Page Builder : seconde interface d'edition des blocs racine d'une page. */
class PageBuilderController extends Controller
{
    public function show(Page $page): View
    {
        $labels = config('page_blocks.types');

        $blocks = $page->blocks()
            ->whereNull('parent_id')
            ->withCount('children')
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
                    ? ['data' => $block->data ?? []]
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
            ],
        ]);
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
        ]);

        return response()->json([
            'success' => true,
            'blocks' => $sync->sync($page, $payload['blocks']),
        ]);
    }
}
