<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesBlocks;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\BlockMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Blocs de premier niveau d'une page. */
class PageBlockController extends Controller
{
    use ManagesBlocks;

    /**
     * Champs figés après création. Changer column_count casserait les
     * enfants déjà répartis (colonne 4 orpheline si on passe de 5 à 3).
     */
    private const LOCKED_FIELDS = ['colonnes' => ['column_count']];

    public function __construct(private BlockMediaService $blockMedia) {}

    public function index(Page $page)
    {
        return view('admin.pages.blocks.index', [
            'page' => $page,
            'blocks' => $page->blocks()->whereNull('parent_id')->get(),
            'types' => config('page_blocks.types'),
        ]);
    }

    public function create(Page $page, string $type)
    {
        $this->ensureKnownType($type);

        return view('admin.pages.blocks.create', [
            'page' => $page,
            'type' => $type,
            'pdfDocuments' => $this->pdfDocumentsFor($type),
        ]);
    }

    public function store(Request $request, Page $page): RedirectResponse
    {
        $type = (string) $request->input('type');
        $this->ensureKnownType($type);

        $this->saveNew($request, $page, $type, [
            'order' => $page->blocks()->whereNull('parent_id')->max('order') + 1,
        ]);

        return $this->toIndex($page, 'Le bloc a été ajouté avec succès.');
    }

    public function edit(Page $page, int $blockId)
    {
        $block = $this->topLevelBlock($page, $blockId);

        return view('admin.pages.blocks.edit', [
            'page' => $page,
            'block' => $block,
            'pdfDocuments' => $this->pdfDocumentsFor($block->type),
        ]);
    }

    public function update(Request $request, Page $page, int $blockId): RedirectResponse
    {
        $block = $this->topLevelBlock($page, $blockId);

        $this->saveExisting($request, $block, self::LOCKED_FIELDS[$block->type] ?? []);

        return $this->toIndex($page, 'Le bloc a été modifié avec succès.');
    }

    public function destroy(Page $page, int $blockId): RedirectResponse
    {
        $block = $this->topLevelBlock($page, $blockId);

        $this->pruneMediaRecursively($block);
        $block->delete();

        return $this->toIndex($page, 'Le bloc a été supprimé avec succès.');
    }

    public function reorder(Request $request, Page $page)
    {
        $ids = $this->orderedIds($request);

        $found = $page->blocks()->whereNull('parent_id')->whereIn('id', $ids)->count();
        if ($found !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['order' => 'Un ou plusieurs blocs ne sont pas des blocs racine de cette page.']);
        }

        foreach ($ids as $index => $blockId) {
            $page->blocks()->whereNull('parent_id')->where('id', $blockId)->update(['order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    private function toIndex(Page $page, string $message): RedirectResponse
    {
        return redirect()->route('admin.pages.blocks.index', $page)->with('success', $message);
    }
}
