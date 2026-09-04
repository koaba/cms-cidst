<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\ThumbnailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateMediaThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $mediaId;

    public function __construct(Media $media)
    {
        $this->mediaId = $media->id;
    }

    public function handle(ThumbnailService $thumbnailService): void
    {
        $media = Media::find($this->mediaId);

        if (! $media) {
            Log::info("GenerateMediaThumbnail : Media #{$this->mediaId} introuvable (probablement supprimé entre le dispatch et l'exécution du job), job ignoré.");

            return;
        }

        $thumbnailPath = $thumbnailService->generate($media->path);

        if ($thumbnailPath) {
            $media->updateQuietly(['thumbnail_path' => $thumbnailPath]);
        }
    }
}