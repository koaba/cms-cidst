<?php

namespace App\Services;

use App\Jobs\ProcessMediaImage;
use App\Models\Media;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Convertit en WebP les images JPEG et PNG deja presentes sur le disque public
 * et reecrit, dans la meme transaction, tous les chemins qui les designent.
 *
 * Ordre sur pour un fichier : le WebP est ecrit, la base est mise a jour, puis
 * seulement l'original est supprime. Une panne laisse au pire un fichier orphelin,
 * jamais un lien casse.
 */
class ExistingImageConverter
{
    private const EXTENSIONS = ['jpg', 'jpeg', 'png'];

    /** Colonnes (hors medias et logo) qui stockent un chemin brut du disque public. */
    private const PATH_COLUMNS = [
        'sliders' => 'image',
        'articles' => 'image',
        'seo_metas' => 'og_image',
    ];

    public function __construct(private ImageOptimizer $optimizer) {}

    /**
     * Chemins distincts des JPEG et PNG existants sur le disque, references par un media,
     * le logo du site, un slider, un article ou une image sociale.
     *
     * @return list<string>
     */
    public function candidates(): array
    {
        $disk = Storage::disk('public');

        $paths = Media::query()->where('type', 'image')->whereNotNull('path')->pluck('path')
            ->merge(DB::table('site_settings')->whereNotNull('logo_path')->pluck('logo_path'));

        foreach (self::PATH_COLUMNS as $table => $column) {
            $paths = $paths->merge(DB::table($table)->whereNotNull($column)->pluck($column));
        }

        return $paths
            ->map(fn ($path) => (string) $path)
            ->filter(fn (string $path) => in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::EXTENSIONS, true)
                && $disk->exists($path))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Convertit un fichier. Retourne le nouveau chemin, ou null s'il n'a pas ete converti.
     * Si la base ne peut pas etre mise a jour, le WebP est retire, l'original reste et
     * l'exception est relancee.
     */
    public function convert(string $path): ?string
    {
        $new = $this->optimizer->convert($path);

        if ($new === $path) {
            return null;
        }

        $disk = Storage::disk('public');

        try {
            $converted = DB::transaction(fn () => $this->rewriteReferences(
                $path,
                $new,
                $disk->size($new),
                MediaDimensionsReader::read($new),
            ));
        } catch (Throwable $e) {
            $disk->delete($new);

            throw $e;
        }

        $disk->delete($path);

        foreach ($converted as ['media' => $media, 'stale' => $stale]) {
            $disk->delete($stale);
            ProcessMediaImage::dispatchSync($media);
        }

        return $new;
    }

    /**
     * @param  array{width: int, height: int}|array{}  $dimensions
     * @return list<array{media: Media, stale: list<string>}>
     */
    private function rewriteReferences(string $old, string $new, int $size, array $dimensions): array
    {
        $converted = [];

        foreach (Media::query()->where('path', $old)->get() as $media) {
            $stale = array_values(array_filter([$media->thumbnail_path, ...array_values($media->variants ?? [])]));

            // Miniature et variantes sont regenerees depuis le WebP : leurs anciens chemins sont perimes.
            $media->update([
                'path' => $new,
                'mime_type' => 'image/webp',
                'size' => $size,
                'thumbnail_path' => null,
                'variants' => null,
            ] + $dimensions);

            $converted[] = ['media' => $media, 'stale' => $stale];
        }

        foreach (self::PATH_COLUMNS as $table => $column) {
            DB::table($table)->where($column, $old)->update([$column => $new]);
        }

        // Par le modele : l'enregistrement vide le cache des reglages du site.
        SiteSetting::query()->where('logo_path', $old)->get()
            ->each(fn (SiteSetting $settings) => $settings->update(['logo_path' => $new]));

        return $converted;
    }
}
