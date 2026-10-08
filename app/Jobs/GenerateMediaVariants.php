<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\ImageVariantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateMediaVariants implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $mediaId;

    public function __construct(Media $media)
    {
        $this->mediaId = $media->id;
    }

    public function handle(ImageVariantService $variantService): void
    {
        $media = Media::find($this->mediaId);

        if (! $media) {
            Log::info("GenerateMediaVariants : Media #{$this->mediaId} introuvable (supprime avant l'execution du job), job ignore.");

            return;
        }

        $variants = $variantService->generate($media->path);

        if ($variants !== []) {
            $media->updateQuietly(['variants' => $variants]);
        }
    }
}
