<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesBlocks;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageBlock;
use App\Services\BlockMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Blocs de contenu placés à l'intérieur d'un item d'accordéon. */
class AccordionItemContentController extends Controller
{
    use ManagesBlocks;

    public function __construct(private BlockMediaService $blockMedia) {}

    public function create(Page $page, int $blockId, int $itemId, string $type)
    {
        $parent = $this->accordionParent($page, $blockId);
        $item = $this->accordionItem($parent, $itemId);
        $this->ensureNestable($type);

        return view('admin.pages.blocks.create-item-content', [
            'page' => $page,
            'parent' => $parent,
            'item' => $item,
            'type' => $type,
            'pdfDocuments' => $this->pdfDocumentsFor($type),
        ]);
    }

    public function store(Request $request, Page $page, int $blockId, int $itemId): RedirectResponse
    {
        $parent = $this->accordionParent($page, $blockId);
        $item = $this->accordionItem($parent, $itemId);
        $type = (string) $request->input('type');
        $this->ensureNestable($type);

        $this->saveNew($request, $page, $type, [
            'parent_id' => $item->id,
            'order' => $item->children()->max('order') + 1,
        ]);

        return $this->toItem($page, $parent, $item, "Contenu ajouté à l'item avec succès.");
    }

    public function edit(Page $page, int $blockId, int $itemId, int $contentId)
    {
        [$parent, $item, $content] = $this->resolve($page, $blockId, $itemId, $contentId);

        return view('admin.pages.blocks.edit-item-content', [
            'page' => $page,
            'parent' => $parent,
            'item' => $item,
            'content' => $content,
            'pdfDocuments' => $this->pdfDocumentsFor($content->type),
        ]);
    }

    public function update(Request $request, Page $page, int $blockId, int $itemId, int $contentId): RedirectResponse
    {
        [$parent, $item, $content] = $this->resolve($page, $blockId, $itemId, $contentId);

        $this->saveExisting($request, $content);

        return $this->toItem($page, $parent, $item, 'Contenu modifié avec succès.');
    }

    public function destroy(Page $page, int $blockId, int $itemId, int $contentId): RedirectResponse
    {
        [$parent, $item, $content] = $this->resolve($page, $blockId, $itemId, $contentId);

        $this->pruneMediaRecursively($content);
        $content->delete();

        return $this->toItem($page, $parent, $item, "Contenu retiré de l'item avec succès.");
    }

    /** @return array{0: PageBlock, 1: PageBlock, 2: PageBlock} accordéon, item, contenu */
    private function resolve(Page $page, int $blockId, int $itemId, int $contentId): array
    {
        $parent = $this->accordionParent($page, $blockId);
        $item = $this->accordionItem($parent, $itemId);

        return [$parent, $item, $item->children()->findOrFail($contentId)];
    }

    private function toItem(Page $page, PageBlock $parent, PageBlock $item, string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.pages.blocks.items.edit', [$page, $parent->id, $item->id])
            ->with('success', $message);
    }
}
