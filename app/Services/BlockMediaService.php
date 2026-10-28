<?php

namespace App\Services;

use App\Blocks\BlockRegistry;
use App\Contracts\DeclaresMediaFields;
use App\Models\Media;
use App\Models\PageBlock;
use App\Models\PdfCategory;
use App\Models\PdfDocument;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class BlockMediaService
{
    public function __construct(
        private MediaSyncService $mediaSync,
        private WatermarkService $watermarkService,
    ) {}

    public function stripMediaFields(array $data, string $type): array
    {
        $rules = BlockRegistry::rulesFor($type);

        return Arr::except($data, $rules instanceof DeclaresMediaFields ? $rules->mediaFields() : []);
    }

    public function handle(Request $request, PageBlock $block, string $type): void
    {
        match ($type) {
            'image', 'banniere_hero' => $this->handleSingleImage($request, $block),
            'video' => $this->handleVideo($request, $block),
            'galerie' => $this->handleGalerie($request, $block),
            'pdf' => $this->handlePdf($request, $block),
            default => null,
        };
    }

    /**
     * Rattache un media existant de la mediatheque a un bloc a image unique
     * (image, banniere_hero) et remplace l'ancien. Tout est valide avant la
     * moindre ecriture. L'ancien media n'est supprime que s'il n'est plus
     * utilise nulle part (meme regle que detachOwnedMedia).
     */
    public function attachExisting(PageBlock $block, int $mediaId): void
    {
        $media = Media::findOrFail($mediaId);

        if (! str_starts_with((string) $media->mime_type, 'image/')) {
            throw new InvalidArgumentException('Seule une image peut etre rattachee a ce bloc.');
        }

        $previousIds = $block->media()->pluck('media.id')
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id) => $id === $media->id)
            ->all();

        // Rattacher avant de detacher : un media deja en place ne doit jamais
        // passer par la suppression d'orphelin.
        $block->media()->syncWithoutDetaching([$media->id => ['order' => 0]]);

        $block->detachOwnedMedia($previousIds);
    }

    private function handleSingleImage(Request $request, PageBlock $block): void
    {
        if ($request->boolean('delete_image')) {
            $this->detachAll($block);
        }

        if ($request->hasFile('image')) {
            $this->detachAll($block);

            $media = $this->storeMedia($request->file('image'), 'image', watermark: $request->boolean('apply_watermark'));
            $block->media()->attach($media->id, ['order' => 0]);
        }
    }

    private function handleVideo(Request $request, PageBlock $block): void
    {
        $sourceType = $block->data['source_type'] ?? null;

        if ($request->boolean('delete_video') || $sourceType === 'url') {
            $this->detachAll($block);
        }

        if ($sourceType === 'upload' && $request->hasFile('video_file')) {
            $this->detachAll($block);

            $media = $this->storeMedia($request->file('video_file'), 'video', [
                'apply_watermark' => $request->boolean('apply_watermark'),
            ]);
            $block->media()->attach($media->id, ['order' => 0]);
        }
    }

    private function handleGalerie(Request $request, PageBlock $block): void
    {
        if ($request->filled('delete_media')) {
            $block->detachOwnedMedia($request->input('delete_media'));
        }

        if (! $request->hasFile('images')) {
            return;
        }

        $startOrder = $block->media()->count();
        $alts = $request->input('images_alt', []);
        $captions = $request->input('images_caption', []);

        foreach ($request->file('images') as $index => $file) {
            $media = $this->storeMedia($file, 'image', watermark: $request->boolean('apply_watermark'));

            $block->media()->attach($media->id, [
                'order' => $startOrder + $index,
                'alt' => $alts[$index] ?? null,
                'caption' => $captions[$index] ?? null,
            ]);
        }
    }

    private function handlePdf(Request $request, PageBlock $block): void
    {
        $data = $block->data;
        $data['pdf_document_id'] = $this->resolvePdfDocument($request);
        $block->update(['data' => $data]);
    }

    private function detachAll(PageBlock $block): void
    {
        $block->detachOwnedMedia($block->media()->pluck('media.id')->all());
    }

    private function storeMedia(UploadedFile $file, string $type, array $extra = [], bool $watermark = false): Media
    {
        $path = $file->store('pages', 'public');

        if ($watermark) {
            $this->watermarkService->watermarkImage($path);
        }

        $dimensions = $type === 'image' ? MediaDimensionsReader::read($path) : [];

        return Media::create([
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
            'size' => $file->getSize(),
            'type' => $type,
        ] + $dimensions + $extra);
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
