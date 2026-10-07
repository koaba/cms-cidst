<?php

namespace App\Services;

use App\Models\Media;
use App\Models\PageBlock;

/**
 * Vue d'ensemble de la médiathèque pour le tableau de bord admin.
 * Requêtes agrégées : aucune boucle de requêtes par média.
 */
class MediaOverviewService
{
    /** Au-dessus de ce poids (octets), une image est signalée comme lourde pour le web. */
    public const HEAVY_IMAGE_BYTES = 2 * 1024 * 1024;

    public const RECENT_LIMIT = 6;

    public function overview(): array
    {
        $counts = ['image' => 0, 'pdf' => 0, 'video' => 0, 'other' => 0];
        $totalBytes = 0;

        $groups = Media::query()
            ->selectRaw('type, mime_type, COUNT(*) as total, COALESCE(SUM(size), 0) as bytes')
            ->groupBy('type', 'mime_type')
            ->get();

        foreach ($groups as $group) {
            $counts[$this->kindOf($group->type, $group->mime_type)] += (int) $group->total;
            $totalBytes += (int) $group->bytes;
        }

        return [
            'counts' => $counts,
            'total_bytes' => $totalBytes,
            'total_size' => self::formatBytes($totalBytes),
            'recent' => $this->recent(),
            'alerts' => [
                'orphans' => Media::query()->whereDoesntHave('mediables')->count(),
                'heavy_images' => $this->heavyImages(),
                'missing_alt' => $this->missingAlt(),
            ],
        ];
    }

    /**
     * Poids lisible : 0 o, 1 Ko, 1,5 Mo, 1 Go.
     */
    public static function formatBytes(int $bytes): string
    {
        $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
        $value = (float) $bytes;
        $unit = 0;

        while ($value >= 1024 && $unit < count($units) - 1) {
            $value /= 1024;
            $unit++;
        }

        $text = number_format($value, $unit === 0 ? 0 : 1, ',', ' ');

        if (str_contains($text, ',')) {
            $text = rtrim(rtrim($text, '0'), ',');
        }

        return $text.' '.$units[$unit];
    }

    /**
     * Le type MIME prime : un PDF enregistré avec le type par défaut `image`
     * reste compté comme PDF.
     */
    private function kindOf(?string $type, ?string $mime): string
    {
        if ($mime === 'application/pdf') {
            return 'pdf';
        }

        return match ($type) {
            'video' => 'video',
            'image' => 'image',
            default => 'other',
        };
    }

    private function recent(): array
    {
        return Media::query()
            ->withCount('mediables')
            ->latest()
            ->orderByDesc('id')
            ->take(self::RECENT_LIMIT)
            ->get()
            ->map(function (Media $media) {
                $kind = $this->kindOf($media->type, $media->mime_type);

                return [
                    'name' => $media->original_name ?: ($media->url ?: 'Sans nom'),
                    'kind' => $kind,
                    'size' => self::formatBytes((int) $media->size),
                    'used' => (int) $media->mediables_count,
                    'created_at' => $media->created_at,
                    'thumb' => $kind === 'image' ? $media->thumbnail_url : null,
                ];
            })
            ->all();
    }

    private function heavyImages(): int
    {
        return Media::query()
            ->where('type', 'image')
            ->where('mime_type', 'like', 'image/%')
            ->where('size', '>', self::HEAVY_IMAGE_BYTES)
            ->count();
    }

    /**
     * Le texte alternatif vit à deux endroits : dans le JSON `data` du bloc
     * (image, banniere_hero) et dans le pivot `mediables.alt` (galerie).
     * Calculé en PHP : un `alt` vide est stocké en JSON null, que les
     * requêtes JSON SQL distinguent mal d'une clé absente.
     */
    private function missingAlt(): int
    {
        $single = PageBlock::query()
            ->whereIn('type', ['image', 'banniere_hero'])
            ->whereHas('media')
            ->get(['id', 'type', 'data'])
            ->filter(fn (PageBlock $block) => blank($block->data['alt'] ?? null))
            ->count();

        $gallery = PageBlock::query()
            ->where('type', 'galerie')
            ->with('media')
            ->get()
            ->sum(fn (PageBlock $block) => $block->media
                ->filter(fn (Media $media) => blank($media->pivot->alt))
                ->count());

        return $single + $gallery;
    }
}
