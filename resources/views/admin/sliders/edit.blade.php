<x-admin.layout>
    <h1 class="text-2xl font-bold mb-4">Modifier le slider</h1>

    <form action="{{ route('admin.sliders.update', $slider) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf @method('PUT')

        <div>
            <label class="block font-semibold">Titre</label>
            <input type="text" name="title" value="{{ old('title', $slider->title) }}" class="border w-full p-2 rounded">
            @error('title') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block font-semibold">Sous-titre (optionnel)</label>
            <input type="text" name="subtitle" value="{{ old('subtitle', $slider->subtitle) }}" class="border w-full p-2 rounded">
        </div>

        <div>
            <label class="block font-semibold">Image actuelle</label>
            <img src="{{ Storage::url($slider->image) }}" class="w-32 mb-2 rounded border">
        </div>

        <div>
            <label class="block font-semibold">Nouvelle image (remplace l'existante)</label>
            <input type="file" name="image" accept="image/*" class="border w-full p-2 rounded" onchange="document.getElementById('slider-picked-preview').classList.add('hidden')">
            @error('image') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror

            <div class="mt-2">
                <button type="button" class="text-sm border rounded px-3 py-2 bg-gray-50 hover:bg-gray-100"
                        onclick="MediaPicker.open({ mode: 'single', onConfirm: items => setSliderImage(items[0]) })">
                    Ou choisir depuis la médiathèque
                </button>
                <input type="hidden" name="existing_media_id" id="slider-existing-media-id">
                <div id="slider-picked-preview" class="hidden mt-2">
                    <p class="text-xs text-gray-500 mb-1">Nouvelle image sélectionnée (remplacera l'actuelle après enregistrement) :</p>
                    <img id="slider-picked-img" class="w-24 h-24 object-cover rounded border">
                </div>
            </div>
        </div>

        <div>
            <label class="block font-semibold">Lien au clic (optionnel)</label>
            <input type="text" name="link_url" value="{{ old('link_url', $slider->link_url) }}" class="border w-full p-2 rounded">
        </div>

        <div>
            <label class="block font-semibold">Ordre d'affichage</label>
            <input type="number" name="order" value="{{ old('order', $slider->order) }}" class="border w-full p-2 rounded">
        </div>

        <div>
            <label>
                <input type="checkbox" name="is_active" value="1" {{ $slider->is_active ? 'checked' : '' }}> Actif
            </label>
        </div>

        <div class="flex items-center gap-3">
            <x-admin.button type="submit">Mettre à jour</x-admin.button>
            <a href="{{ route('admin.sliders.index') }}" class="btn btn-ghost">Annuler</a>
        </div>
    </form>

    <x-admin.media-picker />

    <script>
        function setSliderImage(item) {
            document.getElementById('slider-existing-media-id').value = item.id;
            document.getElementById('slider-picked-img').src = item.url;
            document.getElementById('slider-picked-preview').classList.remove('hidden');
            document.querySelector('input[name="image"]').value = '';
        }
    </script>
</x-admin.layout>