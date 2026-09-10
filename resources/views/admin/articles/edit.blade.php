<x-admin.layout>
    <h1 class="text-2xl font-bold mb-6">Modifier l'article</h1>

    @if ($errors->any())
        <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.articles.update', $article) }}" enctype="multipart/form-data" class="bg-white shadow rounded p-6 max-w-3xl">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block font-medium mb-1">Titre</label>
            <input type="text" name="title" value="{{ old('title', $article->title) }}" class="w-full border rounded p-2">
        </div>

        <div class="mb-4">
            <label class="block font-medium mb-1">Contenu</label>
            <textarea name="content" rows="8" class="w-full border rounded p-2">{{ old('content', $article->content) }}</textarea>
        </div>

        <div class="mb-4">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_published" value="1" {{ old('is_published', $article->is_published) ? 'checked' : '' }}>
                Publier immédiatement
            </label>
        </div>

        <div class="mb-4">
            <label class="block font-medium mb-1">Date de publication</label>
            <input type="datetime-local" name="published_at" value="{{ old('published_at', $article->published_at?->format('Y-m-d\TH:i')) }}" class="w-full border rounded p-2">
            <p class="text-xs text-gray-500 mt-1">Modifie cette date pour antidater ou programmer la publication.</p>
        </div>

        <div class="mb-4">
            <label class="block font-medium mb-1">Affichage de la galerie</label>
            <select name="gallery_display" class="w-full border rounded p-2">
                <option value="grid" {{ old('gallery_display', $article->gallery_display) === 'grid' ? 'selected' : '' }}>Grille</option>
                <option value="slideshow" {{ old('gallery_display', $article->gallery_display) === 'slideshow' ? 'selected' : '' }}>Diaporama</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="block font-medium mb-1">Image à la une</label>
            <input type="file" name="image" accept="image/*" class="w-full border rounded p-2">
            @if ($article->image)
                <img src="{{ Storage::url($article->image) }}" class="w-32 h-32 object-cover rounded border mt-2">
            @endif
            <x-admin.watermark-checkbox
                name="apply_watermark_cover_image"
                id="watermark-cover-image"
                :checked="old('apply_watermark_cover_image', \App\Models\SiteSetting::current()->image_watermark_default_enabled)"
                label="Appliquer le filigrane de protection sur la nouvelle image à la une"
            />
        </div>

        {{-- ===================== GALERIE SIMPLE ===================== --}}
        @php
            // media() mélange désormais images et PDF joints : on sépare ici pour l'affichage.
            $galleryImages = $article->media->filter(fn ($m) => str_starts_with($m->mime_type, 'image/'));
            $attachedPdfs = $article->media->filter(fn ($m) => $m->mime_type === 'application/pdf');
        @endphp
        <div class="mb-6 border-t pt-4" data-dropzone>
    <h2 class="font-semibold mb-2">Galerie d'images <span class="text-xs text-gray-500 font-normal">(20 max)</span></h2>

    <x-admin.media-reorder-list
        :items="$galleryImages"
        variant="image"
        model-type="article"
        :model-id="$article->id"
        delete-field="delete_images[]"
        width-class="w-24"
        height-class="h-24"
    />

    <x-admin.media-add-controls
        preview-container-id="gallery-selected"
        upload-field-name="images[]"
        pick-field-name="existing_media[]"
        accept="image/*"
        label="Uploader des images"
    />

    <x-admin.watermark-checkbox
        name="apply_watermark_images"
        id="watermark-gallery-images"
        :checked="old('apply_watermark_images', \App\Models\SiteSetting::current()->image_watermark_default_enabled)"
        label="Appliquer le filigrane de protection sur les nouvelles images"
    />
</div>

        {{-- ===================== DOCUMENTS PDF ===================== --}}
        <div class="mb-6 border-t pt-4" data-dropzone>
            <h2 class="font-semibold mb-2">Documents PDF <span class="text-xs text-gray-500 font-normal">(10 max, visibles publiquement sur la page article)</span></h2>

           <x-admin.media-reorder-list
    :items="$attachedPdfs"
    variant="pdf"
    model-type="article"
    :model-id="$article->id"
    delete-field="delete_pdfs[]"
/>

            <label class="text-sm border rounded px-3 py-2 cursor-pointer bg-gray-50 hover:bg-gray-100 inline-block mb-2">
                + Ajouter des PDF
                <input
                    type="file"
                    name="pdfs[]"
                    id="pdf-input"
                    class="hidden js-pdf-thumbnail-input"
                    accept="application/pdf"
                    multiple
                    data-preview="pdf-thumbnails-preview"
                    data-data-input="pdf-thumbnails-data"
                >
            </label>
            <div id="pdf-thumbnails-preview" class="flex flex-wrap gap-2 mb-2"></div>
            <input type="hidden" name="pdf_thumbnails" id="pdf-thumbnails-data">

            <x-admin.watermark-checkbox
                name="apply_watermark_pdfs"
                id="watermark-pdfs"
                :checked="old('apply_watermark_pdfs', \App\Models\SiteSetting::current()->pdf_watermark_default_enabled)"
                label="Appliquer le filigrane de protection sur ces documents"
                class=""
            />
        </div>

        {{-- ===================== DIAPORAMAS ===================== --}}
        <div class="mb-6 border-t pt-4">
            <h2 class="font-semibold mb-2">Diaporamas <span class="text-xs text-gray-500 font-normal">(4 max, 10 images max chacun)</span></h2>

            <div id="diaporamas-container" class="space-y-4" data-initial-count="{{ $article->diaporamas->count() }}" data-max-diaporamas="{{ config('media.max_diaporamas') }}" data-max-images-per-diaporama="{{ config('media.max_images_per_diaporama') }}">
                @foreach ($article->diaporamas as $i => $diaporama)
                    <div class="border rounded p-3" id="diaporama-{{ $i }}" data-dropzone>
                        <input type="hidden" name="diaporamas[{{ $i }}][id]" value="{{ $diaporama->id }}">
                        <div class="flex flex-wrap justify-between items-center gap-2 mb-2">
                            <input type="text" name="diaporamas[{{ $i }}][title]" value="{{ $diaporama->title }}" placeholder="Titre du diaporama (optionnel)" class="border rounded p-2 text-sm flex-1 mr-2">
                            <label class="flex items-center gap-1 text-xs text-red-600">
                                <input type="checkbox" name="delete_diaporamas[]" value="{{ $diaporama->id }}">
                                Supprimer tout le diaporama
                            </label>
                        </div>
                        <x-admin.media-reorder-list
    :items="$diaporama->media"
    variant="image"
    model-type="diaporama"
    :model-id="$diaporama->id"
    delete-field="diaporamas[{{ $i }}][delete_images][]"
    width-class="w-20"
    height-class="h-20"
/>

<x-admin.media-add-controls
    preview-container-id="diaporama-{{ $i }}-selected"
    upload-field-name="diaporamas[{{ $i }}][images][]"
    pick-field-name="diaporamas[{{ $i }}][existing_media][]"
    accept="image/*"
    label="Uploader"
/>
                    </div>
                @endforeach
            </div>

            <button type="button" id="add-diaporama-btn" class="text-sm border rounded px-3 py-2 bg-gray-50 hover:bg-gray-100 mt-2" onclick="ArticleForm.addDiaporama()">
                + Ajouter un diaporama
            </button>

            <x-admin.watermark-checkbox
                name="apply_watermark_diaporamas"
                id="watermark-diaporamas"
                :checked="old('apply_watermark_diaporamas', \App\Models\SiteSetting::current()->diaporama_watermark_default_enabled)"
                label="Appliquer le filigrane de protection sur les images des diaporamas"
            />
        </div>

        {{-- ===================== VIDÉOS ===================== --}}
        <div class="mb-6 border-t pt-4">
            <h2 class="font-semibold mb-2">Vidéos <span class="text-xs text-gray-500 font-normal">(5 max, upload MP4/WebM 15 Mo max, ou lien externe)</span></h2>

                      <div id="videos-container" class="space-y-2" data-initial-count="{{ $article->videoMedia->count() }}" data-new-container="videos-new-container" data-watermark-default="{{ \App\Models\SiteSetting::current()->video_watermark_default_enabled ? '1' : '0' }}" data-max-videos="{{ config('media.max_videos') }}" data-max-video-kb="{{ config('media.max_video_upload_kb') }}">
                     @foreach ($article->videoMedia as $video)
                     <div class="border rounded p-3" data-dropzone>
                        <input type="hidden" name="videos[{{ $loop->index }}][id]" value="{{ $video->id }}">
                        <input type="hidden" name="videos[{{ $loop->index }}][source_type]" value="{{ $video->source_type }}">

                        <div class="flex flex-wrap justify-between items-center gap-2 mb-2">
                            <input type="text" name="videos[{{ $loop->index }}][title]" value="{{ $video->title }}" placeholder="Titre (optionnel)" class="border rounded p-2 text-sm flex-1 mr-2">
                            <label class="flex items-center gap-1 text-xs text-red-600">
                                <input type="checkbox" name="delete_videos[]" value="{{ $video->id }}">
                                Supprimer
                            </label>
                        </div>

                        <label class="flex items-center gap-1 text-xs text-gray-600 mb-2">
                            <input type="checkbox" name="videos[{{ $loop->index }}][apply_watermark]" value="1" @checked($video->apply_watermark)>
                            Appliquer le filigrane
                        </label>

                        @if ($video->source_type === 'upload')
                            <p class="text-xs text-gray-500 mb-1">Fichier actuel : {{ basename($video->path) }}</p>
                            <input type="file" name="videos[{{ $loop->index }}][file]" accept="video/mp4,video/webm" class="w-full border rounded p-2 text-sm">
                            <p class="text-xs text-gray-500 mt-1">Laisse vide pour garder le fichier actuel, ou choisis-en un nouveau pour le remplacer (MP4/WebM, 15 Mo max).</p>
                        @else
                            <input type="url" name="videos[{{ $loop->index }}][url]" value="{{ $video->url }}" placeholder="https://youtube.com/..." class="w-full border rounded p-2 text-sm">
                        @endif
                    </div>
                @endforeach
            </div>

            <div id="videos-new-container" class="space-y-4 mt-2"></div>

            <button type="button" id="add-video-btn" class="text-sm border rounded px-3 py-2 bg-gray-50 hover:bg-gray-100 mt-2" onclick="ArticleForm.addVideo()">
                + Ajouter une vidéo
            </button>
        </div>

        <div class="mb-4 border-t pt-4">
            <label class="flex items-center gap-2">
                <input type="checkbox" id="toggle-categories"
                       {{ $article->categories->isNotEmpty() ? 'checked' : '' }}
                       onchange="document.getElementById('categories-field').classList.toggle('hidden', !this.checked)">
                Ajouter des catégories
            </label>
            <div id="categories-field" class="{{ $article->categories->isNotEmpty() ? '' : 'hidden' }} mt-2 flex flex-wrap gap-3">
                @foreach ($categories as $category)
                    <label class="flex items-center gap-1">
                        <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                               {{ $article->categories->contains($category->id) ? 'checked' : '' }}>
                        {{ $category->name }}
                    </label>
                @endforeach
            </div>
        </div>

        @include('admin.partials.seo-fields', ['model' => $article])

        <div class="flex items-center gap-3 border-t pt-4">
            <x-admin.button type="submit">Mettre à jour</x-admin.button>
            <a href="{{ route('admin.articles.index') }}" class="btn btn-ghost">Annuler</a>
        </div>
    </form>

    <x-admin.media-picker />

    @push('scripts')
        @vite(['resources/js/admin/article-form.js', 'resources/js/admin/pdf-thumbnail.js', 'resources/js/admin/media-reorder.js', 'resources/js/admin/file-dropzone.js'])
    @endpush
</x-admin.layout>