<x-admin.layout>
    <h1 class="text-2xl font-bold mb-4">Nouveau slider</h1>

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
            <input type="file" name="image" class="border w-full p-2 rounded">
            @error('image') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
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
                <input type="checkbox" name="is_active" value="1" checked> Actif
            </label>
        </div>

        <button class="bg-blue-600 text-white px-4 py-2 rounded">Créer</button>
    </form>
</x-admin.layout>