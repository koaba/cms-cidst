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
                name="apply_watermark_images"
                id="watermark-cover-image"
                label="Appliquer le filigrane de protection sur la nouvelle image à la une"
            />
        </div>

        {{-- ===================== GALERIE SIMPLE ===================== --}}
        @php
            // media() mélange désormais images et PDF joints : on sépare ici pour l'affichage.
            $galleryImages = $article->media->filter(fn ($m) => str_starts_with($m->mime_type, 'image/'));
            $attachedPdfs = $article->media->filter(fn ($m) => $m->mime_type === 'application/pdf');
        @endphp
        <div class="mb-6 border-t pt-4">
            <h2 class="font-semibold mb-2">Galerie d'images <span class="text-xs text-gray-500 font-normal">(20 max)</span></h2>

            @if ($galleryImages->isNotEmpty())
                <div class="flex flex-wrap gap-3 mb-3">
                    @foreach ($galleryImages as $media)
                        <label class="block w-24">
                            <img src="{{ Storage::url($media->path) }}" class="w-24 h-24 object-cover rounded border">
                            <span class="flex items-center gap-1 text-xs mt-1">
                                <input type="checkbox" name="delete_images[]" value="{{ $media->id }}">
                                Supprimer
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif

            <div id="gallery-selected" class="flex flex-wrap gap-2 mb-2"></div>

            <div class="flex gap-2">
                <label class="text-sm border rounded px-3 py-2 cursor-pointer bg-gray-50 hover:bg-gray-100">
                    + Uploader des images
                    <input type="file" name="images[]" accept="image/*" multiple class="hidden" onchange="ArticleForm.previewNewUploads(this, 'gallery-selected')">
                </label>
                <button type="button" class="text-sm border rounded px-3 py-2 bg-gray-50 hover:bg-gray-100"
                        onclick="ArticleForm.pickExistingMedia('gallery-selected', 'existing_media[]')">
                    Choisir depuis la médiathèque
                </button>
            </div>

            <x-admin.watermark-checkbox
                name="apply_watermark_images"
                id="watermark-gallery-images"
                label="Appliquer le filigrane de protection sur les nouvelles images"
            />
        </div>

        {{-- ===================== DOCUMENTS PDF ===================== --}}
        <div class="mb-6 border-t pt-4">
            <h2 class="font-semibold mb-2">Documents PDF <span class="text-xs text-gray-500 font-normal">(10 max, visibles publiquement sur la page article)</span></h2>

            @if ($attachedPdfs->isNotEmpty())
                <div class="space-y-2 mb-3">
                    @foreach ($attachedPdfs as $pdf)
                        <label class="flex items-center gap-2 text-sm border rounded px-3 py-2 bg-gray-50">
                            @if ($pdf->thumbnail_path)
                                <img src="{{ Storage::url($pdf->thumbnail_path) }}" class="w-8 h-10 object-cover rounded border">
                            @else
                                <span aria-hidden="true">&#128196;</span>
                            @endif
                            <span class="flex-1">{{ $pdf->original_name }}</span>
                            <span class="flex items-center gap-1 text-xs">
                                <input type="checkbox" name="delete_pdfs[]" value="{{ $pdf->id }}">
                                Supprimer
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif

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
                label="Appliquer le filigrane de protection sur ces documents"
                class=""
            />
        </div>

        {{-- ===================== DIAPORAMAS ===================== --}}
        <div class="mb-6 border-t pt-4">
            <h2 class="font-semibold mb-2">Diaporamas <span class="text-xs text-gray-500 font-normal">(4 max, 10 images max chacun)</span></h2>

            <div id="diaporamas-container" class="space-y-4" data-initial-count="{{ $article->diaporamas->count() }}">
                @foreach ($article->diaporamas as $i => $diaporama)
                    <div class="border rounded p-3" id="diaporama-{{ $i }}">
                        <input type="hidden" name="diaporamas[{{ $i }}][id]" value="{{ $diaporama->id }}">
                        <div class="flex flex-wrap justify-between items-center gap-2 mb-2">
                            <input type="text" name="diaporamas[{{ $i }}][title]" value="{{ $diaporama->title }}" placeholder="Titre du diaporama (optionnel)" class="border rounded p-2 text-sm flex-1 mr-2">
                            <label class="flex items-center gap-1 text-xs text-red-600">
                                <input type="checkbox" name="delete_diaporamas[]" value="{{ $diaporama->id }}">
                                Supprimer tout le diaporama
                            </label>
                        </div>
                                                <label class="flex items-center gap-1 text-xs text-gray-600 mb-2">
                            <input type="checkbox" name="videos[{{ $loop->index }}][apply_watermark]" value="1" @checked($video->apply_watermark)>
                            Appliquer le filigrane
                        </label>

                        @if ($diaporama->media->isNotEmpty())
                            <div class="flex flex-wrap gap-2 mb-2">
                                @foreach ($diaporama->media as $media)
                                    <label class="block w-20">
                                        <img src="{{ Storage::url($media->path) }}" class="w-20 h-20 object-cover rounded border">
                                        <span class="flex items-center gap-1 text-xs">
                                            <input type="checkbox" name="diaporamas[{{ $i }}][delete_images][]" value="{{ $media->id }}">
                                            Suppr.
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        <div id="diaporama-{{ $i }}-selected" class="flex flex-wrap gap-2 mb-2"></div>
                        <div class="flex gap-2">
                            <label class="text-sm border rounded px-3 py-2 cursor-pointer bg-gray-50 hover:bg-gray-100">
                                + Uploader
                                <input type="file" name="diaporamas[{{ $i }}][images][]" accept="image/*" multiple class="hidden" onchange="ArticleForm.previewNewUploads(this, 'diaporama-{{ $i }}-selected')">
                            </label>
                            <button type="button" class="text-sm border rounded px-3 py-2 bg-gray-50 hover:bg-gray-100"
                                    onclick="ArticleForm.pickExistingMedia('diaporama-{{ $i }}-selected', 'diaporamas[{{ $i }}][existing_media][]')">
                                Choisir depuis la médiathèque
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button" id="add-diaporama-btn" class="text-sm border rounded px-3 py-2 bg-gray-50 hover:bg-gray-100 mt-2" onclick="ArticleForm.addDiaporama()">
                + Ajouter un diaporama
            </button>

            <x-admin.watermark-checkbox
                name="apply_watermark_diaporamas"
                id="watermark-diaporamas"
                label="Appliquer le filigrane de protection sur les images des diaporamas"
            />
        </div>

        {{-- ===================== VIDÉOS ===================== --}}
        <div class="mb-6 border-t pt-4">
            <h2 class="font-semibold mb-2">Vidéos <span class="text-xs text-gray-500 font-normal">(5 max, upload MP4/WebM 15 Mo max, ou lien externe)</span></h2>

            <div id="videos-container" class="space-y-2" data-initial-count="{{ $article->videos->count() }}" data-new-container="videos-new-container" data-watermark-default="{{ \App\Models\SiteSetting::current()->video_watermark_default_enabled ? '1' : '0' }}">
                @foreach ($article->videos as $video)
                    <div class="border rounded p-3">
                        <input type="hidden" name="videos[{{ $loop->index }}][id]" value="{{ $video->id }}">
                        <input type="hidden" name="videos[{{ $loop->index }}][source_type]" value="{{ $video->source_type }}">

                        <div class="flex flex-wrap justify-between items-center gap-2 mb-2">
                            <input type="text" name="videos[{{ $loop->index }}][title]" value="{{ $video->title }}" placeholder="Titre (optionnel)" class="border rounded p-2 text-sm flex-1 mr-2">
                            <label class="flex items-center gap-1 text-xs text-red-600">
                                <input type="checkbox" name="delete_videos[]" value="{{ $video->id }}">
                                Supprimer
                            </label>
                        </div>

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
        @vite(['resources/js/admin/article-form.js', 'resources/js/admin/pdf-thumbnail.js'])
    @endpush
</x-admin.layout>