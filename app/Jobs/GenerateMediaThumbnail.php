<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\ThumbnailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateMediaThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly Media $media)
    {
    }

    public function handle(ThumbnailService $thumbnailService): void
    {
        $thumbnailPath = $thumbnailService->generate($this->media->path);

        if ($thumbnailPath) {
            $this->media->updateQuietly(['thumbnail_path' => $thumbnailPath]);
        }
    }
}