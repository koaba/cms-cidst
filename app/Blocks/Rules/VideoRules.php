<?php

namespace App\Blocks\Rules;

use App\Contracts\BlockRules;
use App\Models\PageBlock;
use Illuminate\Http\Request;

class VideoRules implements BlockRules
{
    public function rules(bool $isCreate, ?PageBlock $block = null): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'source_type' => 'required|in:upload,url',
            'url' => 'required_if:source_type,url|nullable|string|max:255',
            'video_file' => array_filter([
                // Fichier requis à la création, ou en mise à jour si le
                // bloc n'a aucun média vidéo (sinon bloc upload vide).
                $isCreate || ! $block?->media()->exists() ? 'required_if:source_type,upload' : null,
                'nullable', 'file', 'mimes:mp4,webm', 'max:15360',
            ]),
            'delete_video' => 'nullable|boolean',
            'apply_watermark' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function casts(Request $request): array
    {
        return [];
    }
}
