<x-admin.layout>
    <h1 class="text-2xl font-bold mb-6">Nouvel article</h1>

    @if ($errors->any())
        <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.articles.store') }}" enctype="multipart/form-data" class="bg-white shadow rounded p-6 max-w-3xl">
        @csrf

        <div class="mb-4">
            <label class="block font-medium mb-1">Titre</label>
            <input type="text" name="title" value="{{ old('title') }}" class="w-full border rounded p-2">
        </div>

        <div class="mb-4">
            <label class="block font-medium mb-1">Contenu</label>
            <textarea name="content" rows="8" class="w-full border rounded p-2">{{ old('content') }}</textarea>
        </div>

        <div class="mb-4">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_published" value="1" {{ old('is_published') ? 'checked' : '' }}>
                Publier immédiatement
            </label>
        </div>

        <div class="mb-4">
            <label class="block font-medium mb-1">Date de publication</label>
            <input type="datetime-local" name="published_at" value="{{ old('published_at', now()->format('Y-m-d\TH:i')) }}" class="w-full border rounded p-2">
            <p class="text-xs text-gray-500 mt-1">Laisse la date actuelle, ou choisis une date passée/future. Un article programmé dans le futur restera invisible au public jusqu'à cette date.</p>
        </div>

        <div class="mb-4">
            <label class="block font-medium mb-1">Affichage de la galerie</label>
            <select name="gallery_display" class="w-full border rounded p-2">
                <option value="grid" {{ old('gallery_display', 'grid') === 'grid' ? 'selected' : '' }}>Grille</option>
                <option value="slideshow" {{ old('gallery_display') === 'slideshow' ? 'selected' : '' }}>Diaporama</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="block font-medium mb-1">Image à la une</label>
            <input type="file" name="image" accept="image/*" class="w-full border rounded p-2">
            <x-admin.watermark-checkbox
                name="apply_watermark_cover_image"
                id="watermark-cover-image"
                :checked="old('apply_watermark_cover_image', \App\Models\SiteSetting::current()->image_watermark_default_enabled)"
                label="Appliquer le filigrane de protection sur l'image à la une"
            />
        </div>

        <div class="mb-6 border-t pt-4">
            <h2 class="font-semibold mb-2">Galerie d'images <span class="text-xs text-gray-500 font-normal">(20 max)</span></h2>

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
                :checked="old('apply_watermark_images', \App\Models\SiteSetting::current()->image_watermark_default_enabled)"
                label="Appliquer le filigrane de protection sur les nouvelles images"
            />
        </div>

        <div class="mb-6 border-t pt-4">
            <h2 class="font-semibold mb-2">Documents PDF <span class="text-xs text-gray-500 font-normal">(10 max, visibles publiquement sur la page article)</span></h2>

            <label class="text-sm border rounded px-3 py-2 cursor-pointer bg-gray-50 hover:bg-gray-100 inline-block mb-2">
                + Ajouter des PDF
                <input type="file" name="pdfs[]" class="hidden js-pdf-thumbnail-input" accept="application/pdf" multiple data-preview="pdf-thumbnails-preview" data-data-input="pdf-thumbnails-data" data-max="10">
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

        <div class="mb-6 border-t pt-4">
            <h2 class="font-semibold mb-2">Diaporamas <span class="text-xs text-gray-500 font-normal">(4 max, 10 images max chacun)</span></h2>

            <div id="diaporamas-container" class="space-y-4"></div>

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

        <div class="mb-6 border-t pt-4">
            <h2 class="font-semibold mb-2">Vidéos <span class="text-xs text-gray-500 font-normal">(5 max, upload MP4/WebM 15 Mo max, ou lien externe)</span></h2>

            <div id="videos-container" class="space-y-4" data-watermark-default="{{ \App\Models\SiteSetting::current()->video_watermark_default_enabled ? '1' : '0' }}"></div>

            <button type="button" id="add-video-btn" class="text-sm border rounded px-3 py-2 bg-gray-50 hover:bg-gray-100 mt-2" onclick="ArticleForm.addVideo()">
                + Ajouter une vidéo
            </button>
        </div>

        <div class="mb-4 border-t pt-4">
            <label class="flex items-center gap-2">
                <input type="checkbox" id="toggle-categories" onchange="document.getElementById('categories-field').classList.toggle('hidden', !this.checked)">
                Ajouter des catégories
            </label>
            <div id="categories-field" class="hidden mt-2 flex flex-wrap gap-3">
                @foreach ($categories as $category)
                    <label class="flex items-center gap-1">
                        <input type="checkbox" name="categories[]" value="{{ $category->id }}">
                        {{ $category->name }}
                    </label>
                @endforeach
            </div>
        </div>

        @include('admin.partials.seo-fields', ['model' => null])

        <div class="flex items-center gap-3 border-t pt-4">
            <x-admin.button type="submit">Créer</x-admin.button>
            <a href="{{ route('admin.articles.index') }}" class="btn btn-ghost">Annuler</a>
        </div>

    </form>

    <x-admin.media-picker />

    @push('scripts')
        @vite(['resources/js/admin/article-form.js', 'resources/js/admin/pdf-thumbnail.js'])
    @endpush
</x-admin.layout>