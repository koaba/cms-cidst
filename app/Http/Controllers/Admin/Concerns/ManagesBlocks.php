<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Blocks\BlockRegistry;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PdfDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Logique commune aux contrôleurs de blocs.
 * Les contrôleurs qui gèrent des médias exposent $blockMedia (BlockMediaService).
 */
trait ManagesBlocks
{
    // ---- Résolution des blocs (toujours bornée à la page) ----

    private function topLevelBlock(Page $page, int $id, ?string $type = null): PageBlock
    {
        return $page->blocks()
            ->whereNull('parent_id')
            ->when($type, fn ($query) => $query->where('type', $type))
            ->findOrFail($id);
    }

    private function accordionParent(Page $page, int $blockId): PageBlock
    {
        return $this->topLevelBlock($page, $blockId, 'accordeon');
    }

    private function accordionItem(PageBlock $parent, int $itemId): PageBlock
    {
        return $parent->children()->where('type', 'accordeon_item')->findOrFail($itemId);
    }

    // ---- Garde-fous de type ----

    private function ensureKnownType(string $type): void
    {
        abort_unless(array_key_exists($type, config('page_blocks.types')), 404);
    }

    /** 404 si le type est inconnu ou absent de nestable_in_columns. */
    private function ensureNestable(string $type): void
    {
        abort_unless(
            array_key_exists($type, config('page_blocks.types'))
            && in_array($type, config('page_blocks.nestable_in_columns', []), true),
            404,
        );
    }

    private function pdfDocumentsFor(string $type): Collection
    {
        return $type === 'pdf' ? PdfDocument::orderBy('title')->get() : collect();
    }

    // ---- Validation et écriture ----

    /** Valide selon les règles du type, puis fusionne les casts. */
    private function validatedData(Request $request, string $type, bool $isCreate, ?PageBlock $block = null): array
    {
        $rules = BlockRegistry::rulesFor($type);
        $validated = $request->validate($rules->rules($isCreate, $block), $rules->messages());

        return array_merge($validated, $rules->casts($request));
    }

    /** Données prêtes à stocker : validées, sans les champs média. */
    private function blockData(Request $request, string $type, bool $isCreate, ?PageBlock $block = null): array
    {
        return $this->blockMedia->stripMediaFields(
            $this->validatedData($request, $type, $isCreate, $block),
            $type,
        );
    }

    /** Crée un bloc de la page puis traite ses médias. $attributes : parent_id, slot_index, order. */
    private function saveNew(Request $request, Page $page, string $type, array $attributes = []): PageBlock
    {
        $block = $page->blocks()->create([
            ...$attributes,
            'type' => $type,
            'data' => $this->blockData($request, $type, true),
        ]);

        $this->blockMedia->handle($request, $block, $type);

        return $block;
    }

    /** Met à jour un bloc ; $locked = clés de data conservées à l'identique. */
    private function saveExisting(Request $request, PageBlock $block, array $locked = []): void
    {
        $data = $this->blockData($request, $block->type, false, $block);

        foreach ($locked as $key) {
            $data[$key] = $block->data[$key];
        }

        $block->update(['data' => $data]);

        $this->blockMedia->handle($request, $block, $block->type);
    }

    /**
     * cascadeOnDelete() supprime les lignes enfants en SQL sans événement
     * Eloquent : le nettoyage des médias se fait donc à la main, du bas vers
     * le haut, à toute profondeur.
     */
    private function pruneMediaRecursively(PageBlock $block): void
    {
        foreach ($block->children as $child) {
            $this->pruneMediaRecursively($child);
        }

        $block->detachAndPruneOrphanMedia($block);
    }

    /** @return array<int, int> ids dans le nouvel ordre */
    private function orderedIds(Request $request): array
    {
        return $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:page_blocks,id',
        ])['order'];
    }
}
