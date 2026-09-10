<?php

namespace App\Services\PdfThumbnail;

use Illuminate\Support\Facades\Storage;

/**
 * Traite les miniatures des documents PDF : génération côté serveur
 * (via PdfThumbnailGeneratorFactory) et validation stricte des miniatures
 * envoyées par le client (générées côté navigateur via pdf.js).
 *
 * Extrait de MediaSyncService pour isoler cette responsabilité, partagée
 * entre Article et PdfDocument, du reste de la synchronisation des médias.
 */
class PdfThumbnailProcessor
{
    /**
     * Génère, côté serveur, la miniature d'un PDF déjà stocké (et déjà
     * filigrané si applicable) sur le disque 'public'. Retourne le chemin
     * relatif de la miniature, ou null en cas d'échec — dans ce cas
     * attachUploadedFiles() retombe sur la miniature client (pdf.js) si
     * elle existe, ou aucune miniature sinon (dégradation gracieuse).
     */
    public function pdfThumbnailGenerator(string $storagePath): \Closure
    {
        return function (string $path) use ($storagePath): ?string {
            $disk = Storage::disk('public');
            $thumbnailPath = $storagePath.'/thumbnails/'.pathinfo($path, PATHINFO_FILENAME).'.jpg';

            $success = PdfThumbnailGeneratorFactory::make()->generate(
                $disk->path($path),
                $disk->path($thumbnailPath),
                400
            );

            return $success ? $thumbnailPath : null;
        };
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
    public function parseThumbnails(?string $raw): array
    {
        if (! $raw) {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return [];
        }

        $validPattern = '/^data:image\/(jpe?g|png|webp);base64,[A-Za-z0-9+\/]+={0,2}$/';

        return collect($decoded)
            ->filter(function ($item) use ($validPattern) {
                if (! is_array($item) || empty($item['name']) || ! is_string($item['name'])) {
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