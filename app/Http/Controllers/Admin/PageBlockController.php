<?php

namespace App\Http\Controllers\Admin;

use App\Blocks\BlockRegistry;
use App\Contracts\BlockRules;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PdfDocument;
use App\Services\BlockMediaService;
use Illuminate\Http\Request;

class PageBlockController extends Controller
{
    public function __construct(
        private BlockMediaService $blockMedia,
    ) {}

    public function index(Page $page)
    {
        $blocks = $page->blocks()->whereNull('parent_id')->get();
        $types = config('page_blocks.types');

        return view('admin.pages.blocks.index', compact('page', 'blocks', 'types'));
    }

    public function create(Page $page, string $type)
    {
        if (! array_key_exists($type, config('page_blocks.types'))) {
            abort(404);
        }

        $pdfDocuments = $type === 'pdf'
            ? PdfDocument::orderBy('title')->get()
            : collect();

        return view('admin.pages.blocks.create', compact('page', 'type', 'pdfDocuments'));
    }

    public function store(Request $request, Page $page)
    {
        $type = $request->input('type');

        if (! array_key_exists($type, config('page_blocks.types'))) {
            abort(404);
        }

        $data = $this->validateForType($request, $type, isCreate: true);
        $data = $this->blockMedia->stripMediaFields($data, $type);

        $block = $page->blocks()->create([
            'type' => $type,
            'data' => $data,
            'order' => $page->blocks()->whereNull('parent_id')->max('order') + 1,
        ]);

        $this->blockMedia->handle($request, $block, $type);

        return redirect()
            ->route('admin.pages.blocks.index', $page)
            ->with('success', 'Le bloc a été ajouté avec succès.');
    }

    public function edit(Page $page, int $blockId)
    {
        $block = $page->blocks()->whereNull('parent_id')->findOrFail($blockId);

        $pdfDocuments = $block->type === 'pdf'
            ? PdfDocument::orderBy('title')->get()
            : collect();

        return view('admin.pages.blocks.edit', compact('page', 'block', 'pdfDocuments'));
    }

    public function update(Request $request, Page $page, int $blockId)
    {
        $block = $page->blocks()->whereNull('parent_id')->findOrFail($blockId);

        $data = $this->validateForType($request, $block->type, isCreate: false, block: $block);
        $data = $this->blockMedia->stripMediaFields($data, $block->type);

        if ($block->type === 'colonnes') {
            // column_count est figé après création : le changer casserait
            // l'affichage des enfants déjà répartis dans les colonnes
            // existantes (colonne 4 orpheline si on repasse de 5 à 3, etc.).
            $data['column_count'] = $block->data['column_count'];
        }

        $block->update(['data' => $data]);

        $this->blockMedia->handle($request, $block, $block->type);

        return redirect()
            ->route('admin.pages.blocks.index', $page)
            ->with('success', 'Le bloc a été modifié avec succès.');
    }

    public function destroy(Page $page, int $blockId)
    {
        $block = $page->blocks()->whereNull('parent_id')->findOrFail($blockId);

        $this->pruneMediaRecursively($block);
        $block->delete();

        return redirect()
            ->route('admin.pages.blocks.index', $page)
            ->with('success', 'Le bloc a été supprimé avec succès.');
    }

    /**
     * cascadeOnDelete() sur parent_id supprime bien les lignes enfants au
     * niveau SQL, à n'importe quelle profondeur, mais ne déclenche aucun
     * événement Eloquent sur elles : sans ce nettoyage manuel récursif,
     * les médias (fichiers + lignes media/mediables) des blocs enfants et
     * petits-enfants resteraient orphelins. Descend jusqu'à la profondeur
     * réelle du bloc concerné (1 niveau pour colonnes, 2 pour accordeon,
     * plus si un futur type imbrique davantage).
     */
    private function pruneMediaRecursively(PageBlock $block): void
    {
        foreach ($block->children as $child) {
            $this->pruneMediaRecursively($child);
        }

        $block->detachAndPruneOrphanMedia($block);
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

    // -----------------------------------------------------------------
    // Gestion des blocs enfants (imbriqués dans une colonne)
    // -----------------------------------------------------------------

    public function createChild(Page $page, int $blockId, int $slotIndex, string $type)
    {
        $parent = $page->blocks()->whereNull('parent_id')->where('type', 'colonnes')->findOrFail($blockId);

        $this->ensureNestable($type);
        $this->ensureSlotIndexInRange($parent, $slotIndex);

        $pdfDocuments = $type === 'pdf'
            ? PdfDocument::orderBy('title')->get()
            : collect();

        return view('admin.pages.blocks.create-child', compact('page', 'parent', 'slotIndex', 'type', 'pdfDocuments'));
    }

    public function storeChild(Request $request, Page $page, int $blockId, int $slotIndex)
    {
        $parent = $page->blocks()->whereNull('parent_id')->where('type', 'colonnes')->findOrFail($blockId);

        $type = $request->input('type');

        $this->ensureNestable($type);
        $this->ensureSlotIndexInRange($parent, $slotIndex);

        $data = $this->validateForType($request, $type, isCreate: true);
        $data = $this->blockMedia->stripMediaFields($data, $type);

        $child = $page->blocks()->create([
            'parent_id' => $parent->id,
            'slot_index' => $slotIndex,
            'type' => $type,
            'data' => $data,
            'order' => $parent->childrenBySlot($slotIndex)->max('order') + 1,
        ]);

        $this->blockMedia->handle($request, $child, $type);

        return redirect()
            ->route('admin.pages.blocks.edit', [$page, $parent->id])
            ->with('success', 'Le bloc a été ajouté à la colonne avec succès.');
    }

    public function editChild(Page $page, int $blockId, int $slotIndex, int $childId)
    {
        $parent = $page->blocks()->whereNull('parent_id')->where('type', 'colonnes')->findOrFail($blockId);
        $this->ensureSlotIndexInRange($parent, $slotIndex);

        $child = $parent->childrenBySlot($slotIndex)->findOrFail($childId);

        $pdfDocuments = $child->type === 'pdf'
            ? PdfDocument::orderBy('title')->get()
            : collect();

        return view('admin.pages.blocks.edit-child', compact('page', 'parent', 'slotIndex', 'child', 'pdfDocuments'));
    }

    public function updateChild(Request $request, Page $page, int $blockId, int $slotIndex, int $childId)
    {
        $parent = $page->blocks()->whereNull('parent_id')->where('type', 'colonnes')->findOrFail($blockId);
        $this->ensureSlotIndexInRange($parent, $slotIndex);

        $child = $parent->childrenBySlot($slotIndex)->findOrFail($childId);

        $data = $this->validateForType($request, $child->type, isCreate: false, block: $child);
        $data = $this->blockMedia->stripMediaFields($data, $child->type);

        $child->update(['data' => $data]);

        $this->blockMedia->handle($request, $child, $child->type);

        return redirect()
            ->route('admin.pages.blocks.edit', [$page, $parent->id])
            ->with('success', 'Le bloc de la colonne a été modifié avec succès.');
    }

    public function destroyChild(Page $page, int $blockId, int $slotIndex, int $childId)
    {
        $parent = $page->blocks()->whereNull('parent_id')->where('type', 'colonnes')->findOrFail($blockId);
        $this->ensureSlotIndexInRange($parent, $slotIndex);

        $child = $parent->childrenBySlot($slotIndex)->findOrFail($childId);

        $child->detachAndPruneOrphanMedia($child);
        $child->delete();

        return redirect()
            ->route('admin.pages.blocks.edit', [$page, $parent->id])
            ->with('success', 'Le bloc a été retiré de la colonne avec succès.');
    }

    // -----------------------------------------------------------------
    // Gestion des items d'accordéon
    // -----------------------------------------------------------------

    private function ensureAccordionParent(Page $page, int $blockId): PageBlock
    {
        return $page->blocks()->whereNull('parent_id')->where('type', 'accordeon')->findOrFail($blockId);
    }

    private function findAccordionItem(PageBlock $parent, int $itemId): PageBlock
    {
        return $parent->children()->where('type', 'accordeon_item')->findOrFail($itemId);
    }

    public function storeAccordionItem(Request $request, Page $page, int $blockId)
    {
        $parent = $this->ensureAccordionParent($page, $blockId);

        $data = $this->validateForType($request, 'accordeon_item', isCreate: true);

        // Pas de borne figée comme column_count : le slot suivant est
        // toujours "après le dernier item existant". Calculé côté serveur,
        // jamais fourni par le client — aucun garde-fou de type
        // ensureSlotIndexInRange() n'est donc nécessaire ici.
        $existingMaxSlot = $parent->children()->where('type', 'accordeon_item')->max('slot_index');
        $nextSlot = is_null($existingMaxSlot) ? 0 : $existingMaxSlot + 1;

        $parent->children()->create([
            'page_id' => $page->id,
            'type' => 'accordeon_item',
            'data' => $data,
            'slot_index' => $nextSlot,
            'order' => $nextSlot,
        ]);

        return redirect()
            ->route('admin.pages.blocks.edit', [$page, $parent->id])
            ->with('success', "Item ajouté à l'accordéon avec succès.");
    }

    public function editAccordionItem(Page $page, int $blockId, int $itemId)
    {
        $parent = $this->ensureAccordionParent($page, $blockId);
        $item = $this->findAccordionItem($parent, $itemId);

        return view('admin.pages.blocks.edit-accordion-item', compact('page', 'parent', 'item'));
    }

    public function updateAccordionItem(Request $request, Page $page, int $blockId, int $itemId)
    {
        $parent = $this->ensureAccordionParent($page, $blockId);
        $item = $this->findAccordionItem($parent, $itemId);

        $data = $this->validateForType($request, 'accordeon_item', isCreate: false);
        $item->update(['data' => $data]);

        return redirect()
            ->route('admin.pages.blocks.items.edit', [$page, $parent->id, $item->id])
            ->with('success', 'Item modifié avec succès.');
    }

    public function destroyAccordionItem(Page $page, int $blockId, int $itemId)
    {
        $parent = $this->ensureAccordionParent($page, $blockId);
        $item = $this->findAccordionItem($parent, $itemId);

        // Un item peut avoir son propre contenu imbriqué (récursion niveau
        // 2) : nettoyage média récursif indispensable, pas seulement sur
        // l'item lui-même.
        $this->pruneMediaRecursively($item);
        $item->delete();

        return redirect()
            ->route('admin.pages.blocks.edit', [$page, $parent->id])
            ->with('success', 'Item supprimé avec succès.');
    }

    public function reorderAccordionItems(Request $request, Page $page, int $blockId)
    {
        $parent = $this->ensureAccordionParent($page, $blockId);

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:page_blocks,id',
        ]);

        foreach ($validated['order'] as $index => $itemId) {
            $parent->children()
                ->where('type', 'accordeon_item')
                ->where('id', $itemId)
                ->update(['slot_index' => $index, 'order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    // -----------------------------------------------------------------
    // Gestion du contenu de chaque item d'accordéon (récursion niveau 2)
    // -----------------------------------------------------------------

    public function createItemContent(Page $page, int $blockId, int $itemId, string $type)
    {
        $parent = $this->ensureAccordionParent($page, $blockId);
        $item = $this->findAccordionItem($parent, $itemId);

        $this->ensureNestable($type);

        $pdfDocuments = $type === 'pdf'
            ? PdfDocument::orderBy('title')->get()
            : collect();

        return view('admin.pages.blocks.create-item-content', compact('page', 'parent', 'item', 'type', 'pdfDocuments'));
    }

    public function storeItemContent(Request $request, Page $page, int $blockId, int $itemId)
    {
        $parent = $this->ensureAccordionParent($page, $blockId);
        $item = $this->findAccordionItem($parent, $itemId);

        $type = $request->input('type');
        $this->ensureNestable($type);

        $data = $this->validateForType($request, $type, isCreate: true);
        $data = $this->blockMedia->stripMediaFields($data, $type);

        $content = $item->children()->create([
            'page_id' => $page->id,
            'type' => $type,
            'data' => $data,
            'order' => $item->children()->max('order') + 1,
        ]);

        $this->blockMedia->handle($request, $content, $type);

        return redirect()
            ->route('admin.pages.blocks.items.edit', [$page, $parent->id, $item->id])
            ->with('success', "Contenu ajouté à l'item avec succès.");
    }

    public function editItemContent(Page $page, int $blockId, int $itemId, int $contentId)
    {
        $parent = $this->ensureAccordionParent($page, $blockId);
        $item = $this->findAccordionItem($parent, $itemId);
        $content = $item->children()->findOrFail($contentId);

        $pdfDocuments = $content->type === 'pdf'
            ? PdfDocument::orderBy('title')->get()
            : collect();

        return view('admin.pages.blocks.edit-item-content', compact('page', 'parent', 'item', 'content', 'pdfDocuments'));
    }

    public function updateItemContent(Request $request, Page $page, int $blockId, int $itemId, int $contentId)
    {
        $parent = $this->ensureAccordionParent($page, $blockId);
        $item = $this->findAccordionItem($parent, $itemId);
        $content = $item->children()->findOrFail($contentId);

        $data = $this->validateForType($request, $content->type, isCreate: false, block: $content);
        $data = $this->blockMedia->stripMediaFields($data, $content->type);

        $content->update(['data' => $data]);
        $this->blockMedia->handle($request, $content, $content->type);

        return redirect()
            ->route('admin.pages.blocks.items.edit', [$page, $parent->id, $item->id])
            ->with('success', 'Contenu modifié avec succès.');
    }

    public function destroyItemContent(Page $page, int $blockId, int $itemId, int $contentId)
    {
        $parent = $this->ensureAccordionParent($page, $blockId);
        $item = $this->findAccordionItem($parent, $itemId);
        $content = $item->children()->findOrFail($contentId);

        $this->pruneMediaRecursively($content);
        $content->delete();

        return redirect()
            ->route('admin.pages.blocks.items.edit', [$page, $parent->id, $item->id])
            ->with('success', "Contenu retiré de l'item avec succès.");
    }

    /**
     * Vérifie que le type existe dans page_blocks.types ET dans
     * nestable_in_columns. 404 sinon (ex. tentative d'imbriquer un
     * bloc `colonnes` dans une colonne, ou un type pas encore implémenté).
     */
    private function ensureNestable(string $type): void
    {
        if (! array_key_exists($type, config('page_blocks.types'))
            || ! in_array($type, config('page_blocks.nestable_in_columns', []), true)
        ) {
            abort(404);
        }
    }

    /**
     * Vérifie que $slotIndex est bien compris dans les slots valides du
     * parent. Pour `colonnes`, la borne haute est column_count - 1. Sans
     * ce garde-fou, une URL forgée (ex. .../columns/99/create/texte)
     * créerait un enfant dans un slot inexistant : enregistré en base
     * mais jamais affiché (données fantômes, pas une faille de sécurité
     * en soi, mais à bloquer proprement).
     */
    private function ensureSlotIndexInRange(PageBlock $parent, int $slotIndex): void
    {
        $slotCount = (int) ($parent->data['column_count'] ?? 0);

        if ($slotIndex < 0 || $slotIndex >= $slotCount) {
            abort(404);
        }
    }

    /**
     * Applique une classe de règles : validation, puis fusion des casts.
     */
    private function validateWith(BlockRules $rules, Request $request, bool $isCreate, ?PageBlock $block): array
    {
        $validated = $request->validate($rules->rules($isCreate, $block), $rules->messages());

        return array_merge($validated, $rules->casts($request));
    }

    private function validateForType(Request $request, string $type, bool $isCreate = false, ?PageBlock $block = null): array
    {
        return $this->validateWith(BlockRegistry::rulesFor($type), $request, $isCreate, $block);
    }
}
