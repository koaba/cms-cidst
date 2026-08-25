<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Diaporama;
use App\Models\PdfDocument;
use App\Models\Video;
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

    public function __construct(private WatermarkService $watermarkService)
    {
    }

    /* ------------------------------------------------------------------ */
    /*  Image à la une (Article uniquement)                               */
    /* ------------------------------------------------------------------ */

    /**
     * Stocke la nouvelle image à la une si un fichier est envoyé, supprime
     * l'ancienne (en update), applique le filigrane si demandé.
     * Retourne le chemin stocké, ou null si aucun fichier n'a été envoyé
     * (dans ce cas, ne pas toucher au champ 'image' de l'article).
     */
    public function syncCoverImage(Request $request, ?Article $article): ?string
    {
        if (!$request->hasFile('image')) {
            return null;
        }

        if ($article && $article->image) {
            Storage::disk('public')->delete($article->image);
        }

        $path = $request->file('image')->store('articles', 'public');

        if ($request->boolean('apply_watermark_images')) {
            $this->watermarkService->watermarkImage($path);
        }

        return $path;
    }

    /* ------------------------------------------------------------------ */
    /*  Galerie simple (Article)                                          */
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
    /*  Documents PDF — Article et PdfDocument                            */
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

        if (!$request->hasFile('pdfs')) {
            return;
        }

        $model->attachUploadedFiles(
            $request->file('pdfs'),
            $storagePath,
            $this->watermarkCallback($request->boolean($watermarkField), 'pdf'),
            $this->parseThumbnails($request->input('pdf_thumbnails'))
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Diaporamas (Article)                                              */
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
            $diaporama = !empty($data['id'])
                ? $article->diaporamas()->find($data['id'])
                : null;

            if (!$diaporama) {
                $diaporama = $article->diaporamas()->create([
                    'title' => $data['title'] ?? null,
                    'order' => $index,
                ]);
            } else {
                $diaporama->update(['title' => $data['title'] ?? $diaporama->title]);
            }

            if (!empty($data['delete_images'])) {
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
    /*  Vidéos (Article)                                                  */
    /* ------------------------------------------------------------------ */

    public function syncVideos(Request $request, Article $article, bool $isUpdate = false): void
    {
        if ($isUpdate && $request->filled('delete_videos')) {
            $ownedIds = $article->videos()->pluck('id')->all();
            foreach (array_intersect($request->input('delete_videos'), $ownedIds) as $videoId) {
                Video::find($videoId)?->delete(); // le model event supprime le fichier si uploadé
            }
        }

        $order = $article->videos()->count();

        foreach ($request->input('videos', []) as $index => $data) {
            $existingVideo = !empty($data['id']) ? $article->videos()->find($data['id']) : null;

            if ($existingVideo) {
                $updates = ['title' => $data['title'] ?? null];

                if ($existingVideo->source_type === 'upload' && $request->hasFile("videos.$index.file")) {
                    Storage::disk('public')->delete($existingVideo->path);
                    $updates['path'] = $request->file("videos.$index.file")->store('articles/videos', 'public');
                } elseif ($existingVideo->source_type === 'external' && !empty($data['url'])) {
                    $updates['url'] = $data['url'];
                }

                $existingVideo->update($updates);
                continue;
            }

            if (($data['source_type'] ?? null) === 'upload' && $request->hasFile("videos.$index.file")) {
                $file = $request->file("videos.$index.file");
                $article->videos()->create([
                    'source_type' => 'upload',
                    'path' => $file->store('articles/videos', 'public'),
                    'title' => $data['title'] ?? null,
                    'order' => $order++,
                ]);
            } elseif (($data['source_type'] ?? null) === 'external' && !empty($data['url'])) {
                $article->videos()->create([
                    'source_type' => 'external',
                    'url' => $data['url'],
                    'title' => $data['title'] ?? null,
                    'order' => $order++,
                ]);
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                           */
    /* ------------------------------------------------------------------ */

    private function watermarkCallback(bool $apply, string $type): ?\Closure
    {
        if (!$apply) {
            return null;
        }

        return $type === 'pdf'
            ? fn (string $path) => $this->watermarkService->watermarkPdf($path)
            : fn (string $path) => $this->watermarkService->watermarkImage($path);
    }

    /**
     * Décode et valide la structure des miniatures PDF envoyées par le client
     * (générées côté navigateur via pdf.js, voir resources/js/admin/pdf-thumbnail.js).
     *
     * Sécurité : le JSON provient d'un champ hidden rempli par du JavaScript
     * client, donc potentiellement modifiable par un utilisateur malveillant
     * avant soumission. On ne fait confiance qu'à la structure attendue :
     * chaque élément doit avoir un `name` (string non vide) et un `thumbnail`
     * qui est soit null, soit une chaîne respectant strictement le format
     * data-URL image en base64. Tout élément non conforme est silencieusement
     * écarté (dégradation gracieuse : au pire l'article/document perd cette
     * miniature, jamais une erreur bloquante pour l'utilisateur).
     */
    private function parseThumbnails(?string $raw): array
    {
        if (!$raw) {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            return [];
        }

        $validPattern = '/^data:image\/(jpe?g|png|webp);base64,[A-Za-z0-9+\/]+={0,2}$/';

        return collect($decoded)
            ->filter(function ($item) use ($validPattern) {
                if (!is_array($item) || empty($item['name']) || !is_string($item['name'])) {
                    return false;
                }

                $thumbnail = $item['thumbnail'] ?? null;

                return $thumbnail === null || (is_string($thumbnail) && preg_match($validPattern, $thumbnail) === 1);
            })
            ->map(fn (array $item) => [
                'name' => $item['name'],
                'thumbnail' => $item['thumbnail'] ?? null,
            ])
            ->values()
            ->all();
    }
}