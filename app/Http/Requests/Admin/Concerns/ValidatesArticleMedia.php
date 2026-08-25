<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Models\Article;
use App\Models\Diaporama;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Factorise les règles de validation partagées entre StoreArticleRequest et
 * UpdateArticleRequest, extraites de l'ancienne méthode
 * ArticleController::validateArticle(). Comportement inchangé, seule la
 * structure change (classes FormRequest standard Laravel au lieu d'une
 * validation manuelle dans le contrôleur).
 */
trait ValidatesArticleMedia
{
    /**
     * @param  ?Article  $article  null en création, l'article existant en édition.
     */
    protected function articleRules(?Article $article): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_published' => 'nullable|boolean',
            'published_at' => 'nullable|date',
            'gallery_display' => 'nullable|in:grid,slideshow',
            'image' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:categories,id',
            'seo' => 'nullable|array',
            'seo.meta_title' => 'nullable|string|max:60',
            'seo.meta_description' => 'nullable|string|max:320',

            // Galerie simple
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,webp|max:4096',
            'apply_watermark_images' => 'nullable|boolean',
            'existing_media' => 'nullable|array',
            'existing_media.*' => 'integer|exists:media,id',
            'delete_images' => 'nullable|array',
            'delete_images.*' => [
                'integer',
                $article
                    ? Rule::exists('mediables', 'media_id')
                        ->where('mediable_type', Article::class)
                        ->where('mediable_id', $article->id)
                    : 'exists:media,id',
            ],

            // Documents PDF (visibles publiquement sur la page article)
            'pdfs' => 'nullable|array',
            'pdfs.*' => 'file|mimes:pdf|max:' . config('media.max_pdf_upload_kb'),
            'apply_watermark_pdfs' => 'nullable|boolean',
            'delete_pdfs' => 'nullable|array',
            'delete_pdfs.*' => [
                'integer',
                $article
                    ? Rule::exists('mediables', 'media_id')
                        ->where('mediable_type', Article::class)
                        ->where('mediable_id', $article->id)
                    : 'exists:media,id',
            ],

            // Diaporamas
            'diaporamas' => 'nullable|array|max:' . config('media.max_diaporamas'),
            'diaporamas.*.id' => 'nullable|integer|exists:diaporamas,id',
            'diaporamas.*.title' => 'nullable|string|max:255',
            'diaporamas.*.images' => 'nullable|array|max:' . config('media.max_images_per_diaporama'),
            'diaporamas.*.images.*' => 'image|mimes:jpeg,png,webp|max:4096',
            'diaporamas.*.existing_media' => 'nullable|array',
            'diaporamas.*.existing_media.*' => 'integer|exists:media,id',
            'diaporamas.*.delete_images' => 'nullable|array',
            'diaporamas.*.delete_images.*' => 'integer|exists:media,id',
            'delete_diaporamas' => 'nullable|array',
            'delete_diaporamas.*' => 'integer|exists:diaporamas,id',
            'apply_watermark_diaporamas' => 'nullable|boolean',

            // Vidéos
            'videos' => 'nullable|array|max:' . config('media.max_videos'),
            'videos.*.source_type' => 'required_with:videos|in:upload,external',
            'videos.*.file' => 'nullable|file|mimes:mp4,webm|max:' . config('media.max_video_upload_kb'),
            'videos.*.url' => 'nullable|url|max:2048',
            'videos.*.title' => 'nullable|string|max:255',
            'delete_videos' => 'nullable|array',
            'delete_videos.*' => 'integer|exists:videos,id',
        ];
    }

    /**
     * Règles de validation dépendant de plusieurs champs à la fois (quotas
     * cumulés), non exprimables comme simples règles par champ. Appelé depuis
     * withValidator() de chaque FormRequest.
     */
    protected function withArticleValidation(Validator $validator, ?Article $article): void
    {
        $validator->after(function ($validator) use ($article) {
            // Galerie simple : total (existantes conservées + nouvelles + sélectionnées) <= 20
            // Ne compte que les images : depuis l'ajout des PDF joints, media() mélange les deux types.
            $currentGalleryCount = $article ? $article->media()->where('mime_type', 'like', 'image/%')->count() : 0;
            $toDelete = count($this->input('delete_images', []));
            $incoming = count($this->file('images', [])) + count($this->input('existing_media', []));
            if (($currentGalleryCount - $toDelete + $incoming) > config('media.max_gallery_images')) {
                $validator->errors()->add('images', 'La galerie ne peut pas dépasser ' . config('media.max_gallery_images') . ' images au total.');
            }

            // Documents PDF : total (existants conservés + nouveaux) <= MAX_PDFS
            $currentPdfCount = $article ? $article->media()->where('mime_type', 'application/pdf')->count() : 0;
            $toDeletePdf = count($this->input('delete_pdfs', []));
            $incomingPdf = count($this->file('pdfs', []));
            if (($currentPdfCount - $toDeletePdf + $incomingPdf) > config('media.max_pdfs')) {
                $validator->errors()->add('pdfs', 'Un article ne peut pas dépasser ' . config('media.max_pdfs') . ' documents PDF.');
            }

            // Chaque diaporama : chaque bloc doit fournir au moins un titre ou des images
            foreach ($this->input('diaporamas', []) as $index => $diaporama) {
                $existingCount = 0;
                if (!empty($diaporama['id'])) {
                    $existingCount = Diaporama::find($diaporama['id'])?->media()->count() ?? 0;
                }
                $toDeleteCount = count($diaporama['delete_images'] ?? []);
                $incomingCount = count($this->file("diaporamas.$index.images", []))
                    + count($diaporama['existing_media'] ?? []);

                if (($existingCount - $toDeleteCount + $incomingCount) > config('media.max_images_per_diaporama')) {
                    $validator->errors()->add(
                        "diaporamas.$index.images",
                        'Un diaporama ne peut pas dépasser ' . config('media.max_images_per_diaporama') . ' images.'
                    );
                }
            }

            // Vidéos : chaque entrée upload doit avoir un fichier, chaque entrée external doit avoir une url
            foreach ($this->input('videos', []) as $index => $video) {
                if (($video['source_type'] ?? null) === 'upload' && !$this->hasFile("videos.$index.file")) {
                    $validator->errors()->add("videos.$index.file", 'Un fichier vidéo est requis pour ce type de source.');
                }
                if (($video['source_type'] ?? null) === 'external' && empty($video['url'])) {
                    $validator->errors()->add("videos.$index.url", 'Une URL est requise pour une vidéo externe.');
                }
            }
        });
    }
}