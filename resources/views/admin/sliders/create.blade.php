<x-admin.layout>
    <h1 class="text-2xl font-bold mb-4">Nouveau slider</h1>

    <p class="text-sm text-gray-500 mb-4">{{ $remainingSlots }} emplacement(s) restant(s) sur {{ config('display.max_sliders') }} maximum.</p>

    <form action="{{ route('admin.sliders.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf

        <div>
            <label class="block font-semibold">Titre</label>
            <input type="text" name="title" value="{{ old('title') }}" class="border w-full p-2 rounded">
            @error('title') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block font-semibold">Sous-titre (optionnel)</label>
            <input type="text" name="subtitle" value="{{ old('subtitle') }}" class="border w-full p-2 rounded">
        </div>

        <div>
            <label class="block font-semibold">Image</label>
            <input type="file" name="image" accept="image/*" class="border w-full p-2 rounded" onchange="document.getElementById('slider-picked-preview').classList.add('hidden')">
            @error('image') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror

            <div class="mt-2">
                <button type="button" class="text-sm border rounded px-3 py-2 bg-gray-50 hover:bg-gray-100"
                        onclick="MediaPicker.open({ mode: 'single', onConfirm: items => setSliderImage(items[0]) })">
                    Ou choisir depuis la médiathèque
                </button>
                <input type="hidden" name="existing_media_id" id="slider-existing-media-id">
                <div id="slider-picked-preview" class="hidden mt-2">
                    <img id="slider-picked-img" class="w-24 h-24 object-cover rounded border">
                </div>
            </div>
        </div>

        <div>
            <label class="block font-semibold">Lien au clic (optionnel)</label>
            <input type="text" name="link_url" value="{{ old('link_url') }}" placeholder="/blog/mon-article" class="border w-full p-2 rounded">
        </div>

        <div>
            <label class="block font-semibold">Ordre d'affichage</label>
            <input type="number" name="order" value="{{ old('order', 0) }}" class="border w-full p-2 rounded">
        </div>

        <div>
            <label>
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}> Actif
            </label>
        </div>

        <div class="flex items-center gap-3">
            <x-admin.button type="submit">Créer</x-admin.button>
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