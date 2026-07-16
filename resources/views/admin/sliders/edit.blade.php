<x-admin.layout>
    <h1 class="text-2xl font-bold mb-4">Modifier le slider</h1>

    <form action="{{ route('admin.sliders.update', $slider) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf @method('PUT')

        <div>
            <label class="block font-semibold">Titre</label>
            <input type="text" name="title" value="{{ old('title', $slider->title) }}" class="border w-full p-2 rounded">
        </div>

        <div>
            <label class="block font-semibold">Sous-titre (optionnel)</label>
            <input type="text" name="subtitle" value="{{ old('subtitle', $slider->subtitle) }}" class="border w-full p-2 rounded">
        </div>

        <img src="{{ Storage::url($slider->image) }}" class="w-32 mb-2">

        <div>
            <label class="block font-semibold">Nouvelle image (remplace l'existante)</label>
            <input type="file" name="image" class="border w-full p-2 rounded">
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

        <button class="bg-blue-600 text-white px-4 py-2 rounded">Mettre à jour</button>
    </form>
</x-admin.layout>