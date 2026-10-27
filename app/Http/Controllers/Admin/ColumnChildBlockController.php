<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesBlocks;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageBlock;
use App\Services\BlockMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Blocs enfants rangés dans les colonnes d'un bloc « colonnes ». */
class ColumnChildBlockController extends Controller
{
    use ManagesBlocks;

    public function __construct(private BlockMediaService $blockMedia) {}

    public function create(Page $page, int $blockId, int $slotIndex, string $type)
    {
        $parent = $this->columnsParent($page, $blockId, $slotIndex);
        $this->ensureNestable($type);

        return view('admin.pages.blocks.create-child', [
            'page' => $page,
            'parent' => $parent,
            'slotIndex' => $slotIndex,
            'type' => $type,
            'pdfDocuments' => $this->pdfDocumentsFor($type),
        ]);
    }

    public function store(Request $request, Page $page, int $blockId, int $slotIndex): RedirectResponse
    {
        $parent = $this->columnsParent($page, $blockId, $slotIndex);
        $type = (string) $request->input('type');
        $this->ensureNestable($type);

        $this->saveNew($request, $page, $type, [
            'parent_id' => $parent->id,
            'slot_index' => $slotIndex,
            'order' => $parent->childrenBySlot($slotIndex)->max('order') + 1,
        ]);

        return $this->toParent($page, $parent, 'Le bloc a été ajouté à la colonne avec succès.');
    }

    public function edit(Page $page, int $blockId, int $slotIndex, int $childId)
    {
        $parent = $this->columnsParent($page, $blockId, $slotIndex);
        $child = $parent->childrenBySlot($slotIndex)->findOrFail($childId);

        return view('admin.pages.blocks.edit-child', [
            'page' => $page,
            'parent' => $parent,
            'slotIndex' => $slotIndex,
            'child' => $child,
            'pdfDocuments' => $this->pdfDocumentsFor($child->type),
        ]);
    }

    public function update(Request $request, Page $page, int $blockId, int $slotIndex, int $childId): RedirectResponse
    {
        $parent = $this->columnsParent($page, $blockId, $slotIndex);
        $child = $parent->childrenBySlot($slotIndex)->findOrFail($childId);

        $this->saveExisting($request, $child);

        return $this->toParent($page, $parent, 'Le bloc de la colonne a été modifié avec succès.');
    }

    public function destroy(Page $page, int $blockId, int $slotIndex, int $childId): RedirectResponse
    {
        $parent = $this->columnsParent($page, $blockId, $slotIndex);
        $child = $parent->childrenBySlot($slotIndex)->findOrFail($childId);

        $this->pruneMediaRecursively($child);
        $child->delete();

        return $this->toParent($page, $parent, 'Le bloc a été retiré de la colonne avec succès.');
    }

    /**
     * Parent « colonnes » dont $slotIndex est valide. Sans cette borne, une
     * URL forgée créerait un enfant dans une colonne inexistante (donnée
     * enregistrée mais jamais affichée).
     */
    private function columnsParent(Page $page, int $blockId, int $slotIndex): PageBlock
    {
        $parent = $this->topLevelBlock($page, $blockId, 'colonnes');

        abort_unless($slotIndex >= 0 && $slotIndex < (int) ($parent->data['column_count'] ?? 0), 404);

        return $parent;
    }

    private function toParent(Page $page, PageBlock $parent, string $message): RedirectResponse
    {
        return redirect()->route('admin.pages.blocks.edit', [$page, $parent->id])->with('success', $message);
    }
}
