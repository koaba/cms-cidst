<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesBlocks;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Items d'un bloc « accordéon » (sans média propre). */
class AccordionItemController extends Controller
{
    use ManagesBlocks;

    public function store(Request $request, Page $page, int $blockId): RedirectResponse
    {
        $parent = $this->accordionParent($page, $blockId);
        $data = $this->validatedData($request, 'accordeon_item', true);

        // Slot suivant calculé côté serveur, jamais fourni par le client :
        // aucun garde-fou d'intervalle nécessaire ici.
        $slot = ($parent->children()->where('type', 'accordeon_item')->max('slot_index') ?? -1) + 1;

        $page->blocks()->create([
            'parent_id' => $parent->id,
            'type' => 'accordeon_item',
            'data' => $data,
            'slot_index' => $slot,
            'order' => $slot,
        ]);

        return redirect()
            ->route('admin.pages.blocks.edit', [$page, $parent->id])
            ->with('success', "Item ajouté à l'accordéon avec succès.");
    }

    public function edit(Page $page, int $blockId, int $itemId)
    {
        $parent = $this->accordionParent($page, $blockId);

        return view('admin.pages.blocks.edit-accordion-item', [
            'page' => $page,
            'parent' => $parent,
            'item' => $this->accordionItem($parent, $itemId),
        ]);
    }

    public function update(Request $request, Page $page, int $blockId, int $itemId): RedirectResponse
    {
        $parent = $this->accordionParent($page, $blockId);
        $item = $this->accordionItem($parent, $itemId);

        $item->update(['data' => $this->validatedData($request, 'accordeon_item', false)]);

        return redirect()
            ->route('admin.pages.blocks.items.edit', [$page, $parent->id, $item->id])
            ->with('success', 'Item modifié avec succès.');
    }

    public function destroy(Page $page, int $blockId, int $itemId): RedirectResponse
    {
        $parent = $this->accordionParent($page, $blockId);
        $item = $this->accordionItem($parent, $itemId);

        // L'item peut contenir des blocs : nettoyage média récursif.
        $this->pruneMediaRecursively($item);
        $item->delete();

        return redirect()
            ->route('admin.pages.blocks.edit', [$page, $parent->id])
            ->with('success', 'Item supprimé avec succès.');
    }

    public function reorder(Request $request, Page $page, int $blockId)
    {
        $parent = $this->accordionParent($page, $blockId);

        foreach ($this->orderedIds($request) as $index => $itemId) {
            $parent->children()
                ->where('type', 'accordeon_item')
                ->where('id', $itemId)
                ->update(['slot_index' => $index, 'order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
