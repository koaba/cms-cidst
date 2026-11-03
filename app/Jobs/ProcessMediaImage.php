<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\ImageVariantService;
use App\Services\ThumbnailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Pipeline unique des images : miniature + variantes WebP, en une seule
 * ecriture sur le Media. Un echec de l'une n'empeche pas l'autre.
 */
class ProcessMediaImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $mediaId;

    public function __construct(Media $media)
    {
        $this->mediaId = $media->id;
    }

    public function handle(ThumbnailService $thumbnails, ImageVariantService $variants): void
    {
        $media = Media::find($this->mediaId);

        if (! $media) {
            Log::info("ProcessMediaImage : Media #{$this->mediaId} introuvable (supprime avant l'execution du job), job ignore.");

            return;
        }

        $updates = array_filter([
            'thumbnail_path' => $thumbnails->generate($media->path),
            'variants' => $variants->generate($media->path) ?: null,
        ], fn ($value) => $value !== null);

        if ($updates !== []) {
            $media->updateQuietly($updates);
        }
    }
}
