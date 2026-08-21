<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Diaporama;
use App\Models\Video;
use App\Services\WatermarkService;
use App\Traits\HasOrphanMediaCleanup;
use App\Concerns\SavesSeoMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    use HasOrphanMediaCleanup, SavesSeoMeta;

    private const MAX_GALLERY_IMAGES = 20;
    private const MAX_DIAPORAMAS = 4;
    private const MAX_IMAGES_PER_DIAPORAMA = 10;
    private const MAX_VIDEOS = 5;
    private const MAX_VIDEO_UPLOAD_KB = 15360; // 15 Mo
    private const MAX_PDFS = 10;
    private const MAX_PDF_UPLOAD_KB = 10240; // 10 Mo par PDF

    private WatermarkService $watermarkService;

    public function __construct(WatermarkService $watermarkService)
    {
        $this->watermarkService = $watermarkService;
    }

    public function index()
    {
        $articles = Article::latest('published_at')->paginate(10);

        return view('admin.articles.index', compact('articles'));
    }

    public function create()
    {
        $categories = Category::all();

        return view('admin.articles.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateArticle($request);

        $article = DB::transaction(function () use ($request, $validated) {
            $validated['user_id'] = auth()->id();
            $validated['published_at'] = $validated['published_at'] ?? now();

                     if ($request->hasFile('image')) {
                $validated['image'] = $request->file('image')->store('articles', 'public');
                if ($request->boolean('apply_watermark_images')) {
                    $this->watermarkService->watermarkImage($validated['image']);
                }
            }

            $article = Article::create($validated);
            $this->saveSeo($article, $validated);
            $article->categories()->sync($validated['categories'] ?? []);

            $this->syncGallery($request, $article);
            $this->syncPdfs($request, $article);
            $this->syncDiaporamas($request, $article);
            $this->syncVideos($request, $article);

            return $article;
        });

        return redirect()->route('admin.articles.index')->with('success', 'Article créé avec succès.');
    }

    public function show(Article $article)
    {
        //
    }

    public function edit(Article $article)
    {
        $article->load(['diaporamas.media', 'videos', 'media']);
        $categories = Category::all();

        return view('admin.articles.edit', compact('article', 'categories'));
    }

    public function update(Request $request, Article $article)
    {
        $validated = $this->validateArticle($request, $article);

        DB::transaction(function () use ($request, $validated, $article) {
            $validated['published_at'] = $validated['published_at'] ?? $article->published_at ?? now();

                       if ($request->hasFile('image')) {
                if ($article->image) {
                    Storage::disk('public')->delete($article->image);
                }
                $validated['image'] = $request->file('image')->store('articles', 'public');
                if ($request->boolean('apply_watermark_images')) {
                    $this->watermarkService->watermarkImage($validated['image']);
                }
            }

            $article->update($validated);
            $this->saveSeo($article, $validated);
            $article->categories()->sync($validated['categories'] ?? []);

            $this->syncGallery($request, $article, isUpdate: true);
            $this->syncPdfs($request, $article, isUpdate: true);
            $this->syncDiaporamas($request, $article, isUpdate: true);
            $this->syncVideos($request, $article, isUpdate: true);
        });

        return redirect()->route('admin.articles.index')->with('success', 'Article modifié avec succès.');
    }

    public function destroy(Article $article)
    {
        $article->load(['diaporamas', 'videos']);

        DB::transaction(function () use ($article) {
            // Galerie simple
            $this->detachAndPruneOrphanMedia($article);

            // Diaporamas : nettoie les médias de chaque diaporama, puis le diaporama lui-même
            foreach ($article->diaporamas as $diaporama) {
                $this->detachAndPruneOrphanMedia($diaporama);
                $diaporama->delete();
            }

            // Vidéos : le model event `deleting` sur Video supprime le fichier si uploadé
            foreach ($article->videos as $video) {
                $video->delete();
            }

            if ($article->image) {
                Storage::disk('public')->delete($article->image);
            }

            $article->delete();
        });

        return redirect()->route('admin.articles.index')->with('success', 'Article supprimé avec succès.');
    }

    /* ------------------------------------------------------------------ */
    /*  Validation                                                        */
    /* ------------------------------------------------------------------ */

    private function validateArticle(Request $request, ?Article $article = null): array
    {
        $validator = Validator::make($request->all(), [
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
            'pdfs.*' => 'file|mimes:pdf|max:' . self::MAX_PDF_UPLOAD_KB,
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
            'diaporamas' => 'nullable|array|max:' . self::MAX_DIAPORAMAS,
            'diaporamas.*.id' => 'nullable|integer|exists:diaporamas,id',
            'diaporamas.*.title' => 'nullable|string|max:255',
            'diaporamas.*.images' => 'nullable|array|max:' . self::MAX_IMAGES_PER_DIAPORAMA,
            'diaporamas.*.images.*' => 'image|mimes:jpeg,png,webp|max:4096',
            'diaporamas.*.existing_media' => 'nullable|array',
            'diaporamas.*.existing_media.*' => 'integer|exists:media,id',
            'diaporamas.*.delete_images' => 'nullable|array',
            'diaporamas.*.delete_images.*' => 'integer|exists:media,id',
            'delete_diaporamas' => 'nullable|array',
            'delete_diaporamas.*' => 'integer|exists:diaporamas,id',
            'apply_watermark_diaporamas' => 'nullable|boolean',

            // Vidéos
            'videos' => 'nullable|array|max:' . self::MAX_VIDEOS,
            'videos.*.source_type' => 'required_with:videos|in:upload,external',
            'videos.*.file' => 'nullable|file|mimes:mp4,webm|max:' . self::MAX_VIDEO_UPLOAD_KB,
            'videos.*.url' => 'nullable|url|max:2048',
            'videos.*.title' => 'nullable|string|max:255',
            'delete_videos' => 'nullable|array',
            'delete_videos.*' => 'integer|exists:videos,id',
        ]);

        $validator->after(function ($validator) use ($request, $article) {
            // Galerie simple : total (existantes conservées + nouvelles + sélectionnées) <= 20
            // Ne compte que les images : depuis l'ajout des PDF joints, media() mélange les deux types.
            $currentGalleryCount = $article ? $article->media()->where('mime_type', 'like', 'image/%')->count() : 0;
            $toDelete = count($request->input('delete_images', []));
            $incoming = count($request->file('images', [])) + count($request->input('existing_media', []));
            if (($currentGalleryCount - $toDelete + $incoming) > self::MAX_GALLERY_IMAGES) {
                $validator->errors()->add('images', 'La galerie ne peut pas dépasser ' . self::MAX_GALLERY_IMAGES . ' images au total.');
            }

            // Documents PDF : total (existants conservés + nouveaux) <= MAX_PDFS
            $currentPdfCount = $article ? $article->media()->where('mime_type', 'application/pdf')->count() : 0;
            $toDeletePdf = count($request->input('delete_pdfs', []));
            $incomingPdf = count($request->file('pdfs', []));
            if (($currentPdfCount - $toDeletePdf + $incomingPdf) > self::MAX_PDFS) {
                $validator->errors()->add('pdfs', 'Un article ne peut pas dépasser ' . self::MAX_PDFS . ' documents PDF.');
            }

            // Chaque diaporama : chaque bloc doit fournir au moins un titre ou des images
            foreach ($request->input('diaporamas', []) as $index => $diaporama) {
                $existingCount = 0;
                if (!empty($diaporama['id'])) {
                    $existingCount = Diaporama::find($diaporama['id'])?->media()->count() ?? 0;
                }
                $toDeleteCount = count($diaporama['delete_images'] ?? []);
                $incomingCount = count($request->file("diaporamas.$index.images", []))
                    + count($diaporama['existing_media'] ?? []);

                if (($existingCount - $toDeleteCount + $incomingCount) > self::MAX_IMAGES_PER_DIAPORAMA) {
                    $validator->errors()->add(
                        "diaporamas.$index.images",
                        'Un diaporama ne peut pas dépasser ' . self::MAX_IMAGES_PER_DIAPORAMA . ' images.'
                    );
                }
            }

            // Vidéos : chaque entrée upload doit avoir un fichier, chaque entrée external doit avoir une url
            foreach ($request->input('videos', []) as $index => $video) {
                if (($video['source_type'] ?? null) === 'upload' && !$request->hasFile("videos.$index.file")) {
                    $validator->errors()->add("videos.$index.file", 'Un fichier vidéo est requis pour ce type de source.');
                }
                if (($video['source_type'] ?? null) === 'external' && empty($video['url'])) {
                    $validator->errors()->add("videos.$index.url", 'Une URL est requise pour une vidéo externe.');
                }
            }
        });

        return $validator->validate();
    }

    /* ------------------------------------------------------------------ */
    /*  Synchronisation galerie simple                                    */
    /* ------------------------------------------------------------------ */

      private function syncGallery(Request $request, Article $article, bool $isUpdate = false): void
    {
        if ($isUpdate && $request->filled('delete_images')) {
            $article->detachOwnedMedia($request->input('delete_images'));
        }

        if ($request->filled('existing_media')) {
            $article->attachExistingMedia($request->input('existing_media'));
        }
        if ($request->hasFile('images')) {
            $applyWatermark = $request->boolean('apply_watermark_images');

            $article->attachUploadedFiles(
                $request->file('images'),
                'articles/gallery',
                $applyWatermark ? fn (string $path) => $this->watermarkService->watermarkImage($path) : null
            );
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Synchronisation documents PDF                                     */
    /* ------------------------------------------------------------------ */

   private function syncPdfs(Request $request, Article $article, bool $isUpdate = false): void
{
    if ($isUpdate && $request->filled('delete_pdfs')) {
        $article->detachOwnedMedia($request->input('delete_pdfs'));
    }

    if (! $request->hasFile('pdfs')) {
        return;
    }

    $applyWatermark = $request->boolean('apply_watermark_pdfs');

    $thumbnails = [];
    if ($request->filled('pdf_thumbnails')) {
        $decoded = json_decode($request->input('pdf_thumbnails'), true);
        if (is_array($decoded)) {
            $thumbnails = $decoded;
        }
    }

    $article->attachUploadedFiles(
        $request->file('pdfs'),
        'articles/pdfs',
        $applyWatermark ? fn (string $path) => $this->watermarkService->watermarkPdf($path) : null,
        $thumbnails
    );
}

    /* ------------------------------------------------------------------ */
    /*  Synchronisation diaporamas                                        */
    /* ------------------------------------------------------------------ */

    private function syncDiaporamas(Request $request, Article $article, bool $isUpdate = false): void
    {
        $applyWatermark = $request->boolean('apply_watermark_diaporamas');

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
                $applyWatermark ? fn (string $path) => $this->watermarkService->watermarkImage($path) : null
            );
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Synchronisation vidéos                                            */
    /* ------------------------------------------------------------------ */

   private function syncVideos(Request $request, Article $article, bool $isUpdate = false): void
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
            // Vidéo existante : mise à jour du titre, remplacement optionnel du fichier/url
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

        // Nouvelle vidéo
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
}