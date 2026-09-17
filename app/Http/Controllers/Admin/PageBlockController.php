<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageBlock;
use Illuminate\Http\Request;
use App\Models\PdfDocument;
use App\Models\PdfCategory;
use App\Services\MediaSyncService;
use App\Services\WatermarkService;

class PageBlockController extends Controller
{
    public function __construct(
        private MediaSyncService $mediaSync,
        private WatermarkService $watermarkService,
    ) {
    }

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

        $data = $this->validateForType($request, $block->type, isCreate: false);
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

        // cascadeOnDelete() sur parent_id supprime bien les lignes enfants
        // au niveau SQL, mais ne déclenche aucun événement Eloquent sur
        // elles : sans ce nettoyage manuel, les médias (fichiers + lignes
        // media/mediables) des blocs enfants resteraient orphelins.
        foreach ($block->children as $child) {
            $child->detachAndPruneOrphanMedia($child);
        }

        $block->detachAndPruneOrphanMedia($block);
        $block->delete();

        return redirect()
            ->route('admin.pages.blocks.index', $page)
            ->with('success', 'Le bloc a été supprimé avec succès.');
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

    public function createChild(Page $page, int $blockId, int $columnIndex, string $type)
    {
        $parent = $page->blocks()->whereNull('parent_id')->where('type', 'colonnes')->findOrFail($blockId);

        $this->ensureNestable($type);
        $this->ensureColumnIndexInRange($parent, $columnIndex);

        $pdfDocuments = $type === 'pdf'
            ? PdfDocument::orderBy('title')->get()
            : collect();

        return view('admin.pages.blocks.create-child', compact('page', 'parent', 'columnIndex', 'type', 'pdfDocuments'));
    }

    public function storeChild(Request $request, Page $page, int $blockId, int $columnIndex)
    {
        $parent = $page->blocks()->whereNull('parent_id')->where('type', 'colonnes')->findOrFail($blockId);

        $type = $request->input('type');

        $this->ensureNestable($type);
        $this->ensureColumnIndexInRange($parent, $columnIndex);

        $data = $this->validateForType($request, $type, isCreate: true);
        $data = $this->stripMediaFields($data);

        $child = $page->blocks()->create([
            'parent_id' => $parent->id,
            'column_index' => $columnIndex,
            'type' => $type,
            'data' => $data,
            'order' => $parent->childrenByColumn($columnIndex)->max('order') + 1,
        ]);

        $this->handleMedia($request, $child, $type);

        return redirect()
            ->route('admin.pages.blocks.edit', [$page, $parent->id])
            ->with('success', 'Le bloc a été ajouté à la colonne avec succès.');
    }

    public function editChild(Page $page, int $blockId, int $columnIndex, int $childId)
    {
        $parent = $page->blocks()->whereNull('parent_id')->where('type', 'colonnes')->findOrFail($blockId);
        $this->ensureColumnIndexInRange($parent, $columnIndex);

        $child = $parent->childrenByColumn($columnIndex)->findOrFail($childId);

        $pdfDocuments = $child->type === 'pdf'
            ? PdfDocument::orderBy('title')->get()
            : collect();

        return view('admin.pages.blocks.edit-child', compact('page', 'parent', 'columnIndex', 'child', 'pdfDocuments'));
    }

    public function updateChild(Request $request, Page $page, int $blockId, int $columnIndex, int $childId)
    {
        $parent = $page->blocks()->whereNull('parent_id')->where('type', 'colonnes')->findOrFail($blockId);
        $this->ensureColumnIndexInRange($parent, $columnIndex);

        $child = $parent->childrenByColumn($columnIndex)->findOrFail($childId);

        $data = $this->validateForType($request, $child->type, isCreate: false);
        $data = $this->stripMediaFields($data);

        $child->update(['data' => $data]);

        $this->handleMedia($request, $child, $child->type);

        return redirect()
            ->route('admin.pages.blocks.edit', [$page, $parent->id])
            ->with('success', 'Le bloc de la colonne a été modifié avec succès.');
    }

    public function destroyChild(Page $page, int $blockId, int $columnIndex, int $childId)
    {
        $parent = $page->blocks()->whereNull('parent_id')->where('type', 'colonnes')->findOrFail($blockId);
        $this->ensureColumnIndexInRange($parent, $columnIndex);

        $child = $parent->childrenByColumn($columnIndex)->findOrFail($childId);

        $child->detachAndPruneOrphanMedia($child);
        $child->delete();

        return redirect()
            ->route('admin.pages.blocks.edit', [$page, $parent->id])
            ->with('success', 'Le bloc a été retiré de la colonne avec succès.');
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
     * Vérifie que $columnIndex est bien compris entre 0 et
     * column_count - 1 du parent. Sans ce garde-fou, une URL forgée
     * (ex. .../columns/99/create/texte) créerait un enfant dans une
     * colonne inexistante : enregistré en base mais jamais affiché
     * (données fantômes, pas une faille de sécurité en soi, mais à
     * bloquer proprement).
     */
    private function ensureColumnIndexInRange(PageBlock $parent, int $columnIndex): void
    {
        $columnCount = (int) ($parent->data['column_count'] ?? 0);

        if ($columnIndex < 0 || $columnIndex >= $columnCount) {
            abort(404);
        }
    }

    private function validateForType(Request $request, string $type, bool $isCreate = false): array
    {
        return match ($type) {
            'texte' => $request->validate([
                'title' => 'nullable|string|max:255',
                'content' => 'required|string',
            ]),
            'separateur' => $request->validate([
                'style' => 'nullable|in:fin,epais',
            ]),
            'citation' => $request->validate([
                'content' => 'required|string',
                'author' => 'nullable|string|max:255',
            ]),
            'bouton' => $request->validate([
                'label' => 'required|string|max:100',
                'url' => 'required|string|max:255',
                'style' => 'nullable|in:primaire,secondaire,outline',
                'new_tab' => 'nullable|boolean',
            ]),
            'image' => $request->validate([
                'image' => ($isCreate ? 'required' : 'nullable') . '|image|max:5120',
                'alt' => 'nullable|string|max:255',
                'caption' => 'nullable|string|max:255',
                'delete_image' => 'nullable|boolean',
                'apply_watermark' => 'nullable|boolean',
            ]),
            'video' => $request->validate([
                'title' => 'nullable|string|max:255',
                'source_type' => 'required|in:upload,url',
                'url' => 'required_if:source_type,url|nullable|string|max:255',
                'video_file' => 'required_if:source_type,upload|nullable|file|mimes:mp4,webm|max:15360',
                'delete_video' => 'nullable|boolean',
                'apply_watermark' => 'nullable|boolean',
            ]),
            'section_fond' => $request->validate([
                'title' => 'nullable|string|max:255',
                'text' => 'required|string',
                'bg_color' => 'nullable|in:gray,blue,dark',
                'button_label' => 'nullable|string|max:100',
                'button_url' => 'nullable|string|max:255',
                'button_new_tab' => 'nullable|boolean',
            ]),
            'galerie' => $request->validate([
                'layout' => 'required|in:grid,carousel',
                'images' => ($isCreate ? 'required' : 'nullable') . '|array|min:1|max:20',
                'images.*' => 'image|max:5120',
                'images_alt' => 'nullable|array',
                'images_alt.*' => 'nullable|string|max:255',
                'images_caption' => 'nullable|array',
                'images_caption.*' => 'nullable|string|max:255',
                'delete_media' => 'nullable|array',
                'delete_media.*' => 'integer',
                'apply_watermark' => 'nullable|boolean',
            ]),
            'pdf' => $request->validate([
                'title' => 'nullable|string|max:255',
                'pdf_source' => 'required|in:existing,new',
                'pdf_document_id' => 'required_if:pdf_source,existing|nullable|exists:pdf_documents,id',
                'pdf_title' => 'required_if:pdf_source,new|nullable|string|max:255',
                'pdfs' => 'required_if:pdf_source,new|nullable|array|max:' . config('media.max_pdfs', 10),
                'pdfs.*' => 'mimes:pdf|max:' . config('media.max_pdf_upload_kb', 10240),
                'apply_watermark' => 'nullable|boolean',
            ]),
            'colonnes' => array_merge(
                $request->validate([
                    'title' => 'nullable|string|max:255',
                    'column_count' => 'required|integer|min:2|max:6',
                ]),
                // Laravel valide correctement une chaîne numérique avec la
                // règle `integer` mais ne la caste pas automatiquement :
                // sans ce cast explicite, column_count serait stocké comme
                // chaîne ("4") dans le JSON `data`.
                ['column_count' => (int) $request->input('column_count')]
            ),
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
        if ($type === 'image') {
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
            if ($request->boolean('delete_video')) {
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