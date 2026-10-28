<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\PageBuilder\BlockSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Page Builder : seconde interface d'edition des blocs racine d'une page. */
class PageBuilderController extends Controller
{
    public function save(Request $request, Page $page, BlockSyncService $sync): JsonResponse
    {
        $payload = $request->validate([
            'blocks' => ['present', 'array', 'max:200'],
            'blocks.*' => ['array'],
            'blocks.*.id' => ['nullable', 'integer'],
            'blocks.*.type' => ['required', 'string'],
            'blocks.*.data' => ['nullable', 'array'],
        ]);

        return response()->json([
            'success' => true,
            'blocks' => $sync->sync($page, $payload['blocks']),
        ]);
    }
}
