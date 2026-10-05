<?php

namespace App\Http\Controllers\Admin;

use App\Blocks\Rules\AccordeonItemRules;
use App\Blocks\Rules\AccordeonRules;
use App\Blocks\Rules\BanniereHeroRules;
use App\Blocks\Rules\BoutonRules;
use App\Blocks\Rules\CitationRules;
use App\Blocks\Rules\ColonnesRules;
use App\Blocks\Rules\GalerieRules;
use App\Blocks\Rules\ImageRules;
use App\Blocks\Rules\PdfRules;
use App\Blocks\Rules\SectionFondRules;
use App\Blocks\Rules\SeparateurRules;
use App\Blocks\Rules\TexteRules;
use App\Blocks\Rules\VideoRules;
use App\Contracts\BlockRules;
use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PdfCategory;
use App\Models\PdfDocument;
use App\Services\MediaSyncService;
use App\Services\WatermarkService;
use Illuminate\Http\Request;

class PageBlockController extends Controller
{
    public function __construct(
        private MediaSyncService $mediaSync,
        private WatermarkService $watermarkService,
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
        $data = $this->stripMediaFields($data);

        $block = $page->blocks()->create([
            'type' => $type,
            'data' => $data,
            'order' => $page->blocks()->whereNull('parent_id')->max('order') + 1,
        ]);

        $this->handleMedia($request, $block, $type);

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
        $data = $this->stripMediaFields($data);

        if ($block->type === 'colonnes') {
            // column_count est figé après création : le changer casserait
            // l'affichage des enfants déjà répartis dans les colonnes
            // existantes (colonne 4 orpheline si on repasse de 5 à 3, etc.).
            $data['column_count'] = $block->data['column_count'];
        }

        $block->update(['data' => $data]);

        $this->handleMedia($request, $block, $block->type);

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
        $data = $this->stripMediaFields($data);

        $child = $page->blocks()->create([
            'parent_id' => $parent->id,
            'slot_index' => $slotIndex,
            'type' => $type,
            'data' => $data,
            'order' => $parent->childrenBySlot($slotIndex)->max('order') + 1,
        ]);

        $this->handleMedia($request, $child, $type);

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

        $data = $this->validateForType($request, $child->type, isCreate: false);
        $data = $this->stripMediaFields($data);

        $child->update(['data' => $data]);

        $this->handleMedia($request, $child, $child->type);

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
        $data = $this->stripMediaFields($data);

        $content = $item->children()->create([
            'page_id' => $page->id,
            'type' => $type,
            'data' => $data,
            'order' => $item->children()->max('order') + 1,
        ]);

        $this->handleMedia($request, $content, $type);

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

        $data = $this->validateForType($request, $content->type, isCreate: false);
        $data = $this->stripMediaFields($data);

        $content->update(['data' => $data]);
        $this->handleMedia($request, $content, $content->type);

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
        return match ($type) {
            'texte' => $this->validateWith(new TexteRules, $request, $isCreate, $block),
            'separateur' => $this->validateWith(new SeparateurRules, $request, $isCreate, $block),
            'citation' => $this->validateWith(new CitationRules, $request, $isCreate, $block),
            'bouton' => $this->validateWith(new BoutonRules, $request, $isCreate, $block),
            'banniere_hero' => $this->validateWith(new BanniereHeroRules, $request, $isCreate, $block),
            'image' => $this->validateWith(new ImageRules, $request, $isCreate, $block),
            'video' => $this->validateWith(new VideoRules, $request, $isCreate, $block),
            'section_fond' => $this->validateWith(new SectionFondRules, $request, $isCreate, $block),
            'galerie' => $this->validateWith(new GalerieRules, $request, $isCreate, $block),

            'pdf' => $this->validateWith(new PdfRules, $request, $isCreate, $block),
            'colonnes' => $this->validateWith(new ColonnesRules, $request, $isCreate, $block),
            // Le bloc accordeon lui-même ne stocke qu'un titre optionnel :
            // les items sont de vrais PageBlock enfants (type accordeon_item),
            // pas des données JSON imbriquées (même logique que `colonnes`).
            'accordeon' => $this->validateWith(new AccordeonRules, $request, $isCreate, $block),
            // accordeon_item : l'en-tête cliquable de chaque item. Le
            // contenu réel de l'item est composé de ses propres enfants
            // PageBlock (récursion), pas stocké ici.
            'accordeon_item' => $this->validateWith(new AccordeonItemRules, $request, $isCreate, $block),
            default => abort(404, "Type de bloc « {$type} » non implémenté."),
        };
    }

    /**
     * Retire du tableau de données validées tout ce qui concerne les
     * fichiers/médias : ces champs sont traités par handleMedia() et ne
     * doivent jamais être stockés tels quels dans la colonne JSON `data`
     * du bloc. Pour le type `pdf`, la référence finale (`pdf_document_id`)
     * est réinjectée par handleMedia() une fois résolue.
     */
    private function stripMediaFields(array $data): array
    {
        unset(
            $data['image'], $data['delete_image'],
            $data['video_file'], $data['delete_video'],
            $data['images'], $data['images_alt'], $data['images_caption'], $data['delete_media'],
            $data['pdf_source'], $data['pdf_document_id'], $data['pdf_title'], $data['pdfs'],
            $data['apply_watermark'],
        );

        return $data;
    }

    private function handleMedia(Request $request, PageBlock $block, string $type): void
    {
        if ($type === 'image' || $type === 'banniere_hero') {
            if ($request->boolean('delete_image')) {
                $block->detachOwnedMedia($block->media()->pluck('media.id')->all());
            }

            if ($request->hasFile('image')) {
                $block->detachOwnedMedia($block->media()->pluck('media.id')->all());

                $file = $request->file('image');
                $path = $file->store('pages', 'public');

                if ($request->boolean('apply_watermark')) {
                    $this->watermarkService->watermarkImage($path);
                }

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
            // Une source url n'utilise aucun fichier : un upload précédent
            // serait inaccessible depuis l'interface (orphelin).
            if ($request->boolean('delete_video') || ($block->data['source_type'] ?? null) === 'url') {
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
                    'apply_watermark' => $request->boolean('apply_watermark'),
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

                    if ($request->boolean('apply_watermark')) {
                        $this->watermarkService->watermarkImage($path);
                    }

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

        if ($type === 'pdf') {
            $documentId = $this->resolvePdfDocument($request);

            $data = $block->data;
            $data['pdf_document_id'] = $documentId;
            $block->update(['data' => $data]);
        }
    }

    /**
     * Résout la référence PdfDocument du bloc : soit un document déjà
     * existant sélectionné en bibliothèque, soit un nouveau document créé
     * à la volée (catégorie "Non classé" auto-créée) dont les fichiers
     * sont synchronisés via l'infrastructure MediaSyncService déjà utilisée
     * par le module Documents PDF classique.
     */
    private function resolvePdfDocument(Request $request): int
    {
        if ($request->input('pdf_source') === 'existing') {
            return (int) $request->input('pdf_document_id');
        }

        // PdfCategory::boot() régénère toujours le slug depuis 'name' à la
        // création (static::creating) : pas besoin de le passer ici.
        $category = PdfCategory::firstOrCreate(['name' => 'Non classé']);

        $document = PdfDocument::create([
            'title' => $request->input('pdf_title'),
            'pdf_category_id' => $category->id,
        ]);

        $this->mediaSync->syncPdfDocument($request, $document);

        return $document->id;
    }
}
