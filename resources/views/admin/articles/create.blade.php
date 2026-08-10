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
        </div>

        <div class="mb-6 border-t pt-4">
            <h2 class="font-semibold mb-2">Galerie d'images <span class="text-xs text-gray-500 font-normal">(20 max)</span></h2>

            <div id="gallery-selected" class="flex flex-wrap gap-2 mb-2"></div>

            <div class="flex gap-2">
                <label class="text-sm border rounded px-3 py-2 cursor-pointer bg-gray-50 hover:bg-gray-100">
                    + Uploader des images
                    <input type="file" name="images[]" accept="image/*" multiple class="hidden" onchange="previewNewUploads(this, 'gallery-selected')">
                </label>
                <button type="button" class="text-sm border rounded px-3 py-2 bg-gray-50 hover:bg-gray-100"
                        onclick="MediaPicker.open({ mode: 'multiple', onConfirm: items => addExistingMedia(items, 'gallery-selected', 'existing_media[]') })">
                    Choisir depuis la médiathèque
                </button>
            </div>
        </div>

        <div class="mb-6 border-t pt-4">
            <h2 class="font-semibold mb-2">Diaporamas <span class="text-xs text-gray-500 font-normal">(4 max, 10 images max chacun)</span></h2>

            <div id="diaporamas-container" class="space-y-4"></div>

            <button type="button" id="add-diaporama-btn" class="text-sm border rounded px-3 py-2 bg-gray-50 hover:bg-gray-100 mt-2" onclick="addDiaporama()">
                + Ajouter un diaporama
            </button>
        </div>

        <div class="mb-6 border-t pt-4">
            <h2 class="font-semibold mb-2">Vidéos <span class="text-xs text-gray-500 font-normal">(5 max, upload MP4/WebM 15 Mo max, ou lien externe)</span></h2>

            <div id="videos-container" class="space-y-4"></div>

            <button type="button" id="add-video-btn" class="text-sm border rounded px-3 py-2 bg-gray-50 hover:bg-gray-100 mt-2" onclick="addVideo()">
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

        <x-admin.button type="submit">Créer</x-admin.button>
        <a href="{{ route('admin.articles.index') }}" class="ml-2 text-gray-600">Annuler</a>
    </form>

    <x-admin.media-picker />

    <script>
        const MAX_DIAPORAMAS = 4;
        const MAX_DIAPORAMA_IMAGES = 10;
        const MAX_VIDEOS = 5;

        let diaporamaCount = 0;
        let videoCount = 0;

        function previewNewUploads(input, containerId) {
            const container = document.getElementById(containerId);
            [...input.files].forEach(file => {
                const chip = document.createElement('div');
                chip.className = 'text-xs bg-gray-100 border rounded px-2 py-1';
                chip.textContent = file.name;
                container.appendChild(chip);
            });
        }

        function addExistingMedia(items, containerId, fieldName) {
            const container = document.getElementById(containerId);
            items.forEach(item => {
                const chip = document.createElement('div');
                chip.className = 'relative inline-block';
                chip.innerHTML = `
                    <img src="${item.url}" class="w-16 h-16 object-cover rounded border" title="${item.name}">
                    <input type="hidden" name="${fieldName}" value="${item.id}">
                `;
                container.appendChild(chip);
            });
        }

        function addDiaporama() {
            if (diaporamaCount >= MAX_DIAPORAMAS) return;
            const index = diaporamaCount++;
            const wrapper = document.createElement('div');
            wrapper.className = 'border rounded p-3';
            wrapper.id = `diaporama-${index}`;
            wrapper.innerHTML = `
                <div class="flex justify-between items-center mb-2">
                    <input type="text" name="diaporamas[${index}][title]" placeholder="Titre du diaporama (optionnel)" class="border rounded p-2 text-sm flex-1 mr-2">
                    <button type="button" onclick="document.getElementById('diaporama-${index}').remove(); updateAddButtons()" class="text-red-600 text-sm">Supprimer</button>
                </div>
                <div id="diaporama-${index}-selected" class="flex flex-wrap gap-2 mb-2"></div>
                <div class="flex gap-2">
                    <label class="text-sm border rounded px-3 py-2 cursor-pointer bg-gray-50 hover:bg-gray-100">
                        + Uploader
                        <input type="file" name="diaporamas[${index}][images][]" accept="image/*" multiple class="hidden" onchange="previewNewUploads(this, 'diaporama-${index}-selected')">
                    </label>
                    <button type="button" class="text-sm border rounded px-3 py-2 bg-gray-50 hover:bg-gray-100" onclick="MediaPicker.open({ mode: 'multiple', onConfirm: items => addExistingMedia(items, 'diaporama-${index}-selected', 'diaporamas[${index}][existing_media][]') })">
                        Choisir depuis la médiathèque
                        </button>
                </div>
            `;
            document.getElementById('diaporamas-container').appendChild(wrapper);
            updateAddButtons();
        }

        function addVideo() {
            if (videoCount >= MAX_VIDEOS) return;
            const index = videoCount++;
            const wrapper = document.createElement('div');
            wrapper.className = 'border rounded p-3';
            wrapper.id = `video-${index}`;
            wrapper.innerHTML = `
                <div class="flex justify-between items-center mb-2">
                    <input type="text" name="videos[${index}][title]" placeholder="Titre de la vidéo (optionnel)" class="border rounded p-2 text-sm flex-1 mr-2">
                    <button type="button" onclick="document.getElementById('video-${index}').remove(); updateAddButtons()" class="text-red-600 text-sm">Supprimer</button>
                </div>
                <div class="flex gap-4 mb-2 text-sm">
                    <label class="flex items-center gap-1">
                        <input type="radio" name="videos[${index}][source_type]" value="upload" checked onchange="toggleVideoSource(${index}, 'upload')">
                        Upload
                    </label>
                    <label class="flex items-center gap-1">
                        <input type="radio" name="videos[${index}][source_type]" value="external" onchange="toggleVideoSource(${index}, 'external')">
                        Lien externe
                    </label>
                </div>
                <div id="video-${index}-upload">
                    <input type="file" name="videos[${index}][file]" accept="video/mp4,video/webm" class="w-full border rounded p-2 text-sm">
                </div>
                <div id="video-${index}-external" class="hidden">
                    <input type="url" name="videos[${index}][url]" placeholder="https://youtube.com/..." class="w-full border rounded p-2 text-sm">
                </div>
            `;
            document.getElementById('videos-container').appendChild(wrapper);
            updateAddButtons();
        }

        function toggleVideoSource(index, type) {
            document.getElementById(`video-${index}-upload`).classList.toggle('hidden', type !== 'upload');
            document.getElementById(`video-${index}-external`).classList.toggle('hidden', type !== 'external');
        }

        function updateAddButtons() {
            const diaporamaVisible = document.getElementById('diaporamas-container').children.length;
            const videoVisible = document.getElementById('videos-container').children.length;
            document.getElementById('add-diaporama-btn').disabled = diaporamaVisible >= MAX_DIAPORAMAS;
            document.getElementById('add-video-btn').disabled = videoVisible >= MAX_VIDEOS;
        }
    </script>
</x-admin.layout>