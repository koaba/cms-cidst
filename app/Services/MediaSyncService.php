<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Diaporama;
use App\Models\Media;
use App\Models\PdfDocument;
use App\Services\PdfThumbnail\PdfThumbnailProcessor;
use App\Traits\HasOrphanMediaCleanup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Centralise la synchronisation des médias (image de couverture, galerie,
 * PDF, diaporamas, vidéos), avec application optionnelle du filigrane.
 * Sert à la fois Article (formulaire complet) et PdfDocument (formulaire PDF
 * seul) pour la partie upload/watermark/miniatures des PDF, afin d'éviter la
 * duplication et de garantir que tout correctif de sécurité (ex. validation
 * stricte du JSON pdf_thumbnails) s'applique aux deux en même temps.
 */
class MediaSyncService
{
    use HasOrphanMediaCleanup;

    public function __construct(
        private WatermarkService $watermarkService,
        private PdfThumbnailProcessor $pdfThumbnailProcessor,
    ) {}

    /* ------------------------------------------------------------------ */
    /*  Image à la une (Article uniquement) */
    /* ------------------------------------------------------------------ */

    /**
     * Stocke la nouvelle image à la une si un fichier est envoyé, supprime
     * l'ancienne (en update), applique le filigrane si demandé.
     * Retourne le chemin stocké, ou null si aucun fichier n'a été envoyé
     * (dans ce cas, ne pas toucher au champ 'image' de l'article).
     *
     * Note : le filigrane de l'image à la une est piloté par son propre
     * champ apply_watermark_cover_image, indépendant de apply_watermark_images
     * (qui ne gouverne que la galerie) — les deux étaient auparavant fusionnés
     * sur un seul champ partagé, ce qui empêchait de les régler séparément.
     */
    public function syncCoverImage(Request $request, ?Article $article): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        if ($article && $article->image) {
            Storage::disk('public')->delete($article->image);
        }

        $path = $request->file('image')->store('articles', 'public');

        if ($request->boolean('apply_watermark_cover_image')) {
            $this->watermarkService->watermarkImage($path);
        }

        return $path;
    }

    /* ------------------------------------------------------------------ */
    /*  Galerie simple (Article) */
    /* ------------------------------------------------------------------ */

    public function syncGallery(Request $request, Article $article, bool $isUpdate = false): void
    {
        if ($isUpdate && $request->filled('delete_images')) {
            $article->detachOwnedMedia($request->input('delete_images'));
        }

        if ($request->filled('existing_media')) {
            $article->attachExistingMedia($request->input('existing_media'));
        }

        if ($request->hasFile('images')) {
            $article->attachUploadedFiles(
                $request->file('images'),
                'articles/gallery',
                $this->watermarkCallback($request->boolean('apply_watermark_images'), 'image')
            );
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Documents PDF — Article et PdfDocument */
    /* ------------------------------------------------------------------ */

    public function syncPdfs(Request $request, Article $article, bool $isUpdate = false): void
    {
        $this->syncPdfFiles($request, $article, 'articles/pdfs', 'apply_watermark_pdfs', $isUpdate);
    }

    /**
     * Équivalent de syncPdfs() pour un PdfDocument (formulaire dédié aux
     * documents PDF, hors contexte article). Extrait de l'ancien
     * PdfDocumentController::syncPdfs(), qui dupliquait cette logique sans
     * bénéficier de la validation stricte de parseThumbnails().
     */
    public function syncPdfDocument(Request $request, PdfDocument $document, bool $isUpdate = false): void
    {
        $this->syncPdfFiles($request, $document, 'pdf-documents/pdfs', 'apply_watermark', $isUpdate);
    }

    /**
     * Logique commune d'attache de fichiers PDF, factorisée entre Article et
     * PdfDocument. $model doit utiliser le trait HasOrderedMediaCollection
     * (attachUploadedFiles, attachExistingMedia, detachOwnedMedia) — non
     * imposé par le système de types PHP (les traits ne se type-hintent pas),
     * mais garanti par les deux seuls appelants de cette méthode.
     */
    private function syncPdfFiles(Request $request, Model $model, string $storagePath, string $watermarkField, bool $isUpdate): void
    {
        if ($isUpdate && $request->filled('delete_pdfs')) {
            $model->detachOwnedMedia($request->input('delete_pdfs'));
        }

        if ($request->filled('existing_media')) {
            $model->attachExistingMedia($request->input('existing_media'));
        }

        if (! $request->hasFile('pdfs')) {
            return;
        }

        $model->attachUploadedFiles(
            $request->file('pdfs'),
            $storagePath,
            $this->watermarkCallback($request->boolean($watermarkField), 'pdf'),
            $this->pdfThumbnailProcessor->parseThumbnails($request->input('pdf_thumbnails')),
            $this->pdfThumbnailProcessor->pdfThumbnailGenerator($storagePath)
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Diaporamas (Article) */
    /* ------------------------------------------------------------------ */

    public function syncDiaporamas(Request $request, Article $article, bool $isUpdate = false): void
    {
        $callback = $this->watermarkCallback($request->boolean('apply_watermark_diaporamas'), 'image');

        if ($isUpdate && $request->filled('delete_diaporamas')) {
            $ownedIds = $article->diaporamas()->pluck('id')->all();
            foreach (array_intersect($request->input('delete_diaporamas'), $ownedIds) as $diaporamaId) {
                $diaporama = Diaporama::find($diaporamaId);
                if ($diaporama) {
                    $this->detachAndPruneOrphanMedia($diaporama);
                    $diaporama->delete();
                }
            }
        }

        foreach ($request->input('diaporamas', []) as $index => $data) {
            $diaporama = ! empty($data['id'])
                ? $article->diaporamas()->find($data['id'])
                : null;

            if (! $diaporama) {
                $diaporama = $article->diaporamas()->create([
                    'title' => $data['title'] ?? null,
                    'order' => $index,
                ]);
            } else {
                $diaporama->update(['title' => $data['title'] ?? $diaporama->title]);
            }

            if (! empty($data['delete_images'])) {
                $diaporama->detachOwnedMedia($data['delete_images']);
            }

            $diaporama->attachExistingMedia($data['existing_media'] ?? []);
            $diaporama->attachUploadedFiles(
                $request->file("diaporamas.$index.images", []),
                'articles/diaporamas',
                $callback
            );
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Vidéos (Article) */
    /* ------------------------------------------------------------------ */

    public function syncVideos(Request $request, Article $article, bool $isUpdate = false): void
    {
        $deletedIds = $request->input('delete_videos', []);
        if ($isUpdate && $request->filled('delete_videos')) {
            $article->detachOwnedMedia($request->input('delete_videos'));
        }

        $order = $article->media()->count();

        foreach ($request->input('videos', []) as $index => $data) {
            if (! empty($data['id']) && in_array($data['id'], $deletedIds)) {
                continue;
            }
            $existingVideo = ! empty($data['id']) ? $article->videoMedia()->find($data['id']) : null;

            if ($existingVideo) {
                $updates = [
                    'original_name' => $data['title'] ?? null,
                    'apply_watermark' => ! empty($data['apply_watermark']),
                ];

                if ($existingVideo->source_type === 'upload' && $request->hasFile("videos.$index.file")) {
                    Storage::disk('public')->delete($existingVideo->path);
                    $file = $request->file("videos.$index.file");
                    $updates['path'] = $file->store('articles/videos', 'public');
                    $updates['mime_type'] = $file->getMimeType() ?? $file->getClientMimeType();
                    $updates['size'] = $file->getSize();
                } elseif ($existingVideo->source_type === 'external' && ! empty($data['url'])) {
                    $updates['url'] = $data['url'];
                }

                $existingVideo->update($updates);

                continue;
            }

            if (($data['source_type'] ?? null) === 'upload' && $request->hasFile("videos.$index.file")) {
                $file = $request->file("videos.$index.file");
                $path = $file->store('articles/videos', 'public');

                $media = Media::create([
                    'type' => 'video',
                    'source_type' => 'upload',
                    'path' => $path,
                    'original_name' => $data['title'] ?? null,
                    'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'apply_watermark' => ! empty($data['apply_watermark']),
                ]);

                $article->media()->attach($media->id, ['order' => $order++]);
            } elseif (($data['source_type'] ?? null) === 'external' && ! empty($data['url'])) {
                $media = Media::create([
                    'type' => 'video',
                    'source_type' => 'external',
                    'url' => $data['url'],
                    'original_name' => $data['title'] ?? null,
                    'apply_watermark' => ! empty($data['apply_watermark']),
                ]);

                $article->media()->attach($media->id, ['order' => $order++]);
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers */
    /* ------------------------------------------------------------------ */

    private function watermarkCallback(bool $apply, string $type): ?\Closure
    {
        if (! $apply) {
            return null;
        }

        return $type === 'pdf'
            ? fn (string $path) => $this->watermarkService->watermarkPdf($path)
            : fn (string $path) => $this->watermarkService->watermarkImage($path);
    }

}
