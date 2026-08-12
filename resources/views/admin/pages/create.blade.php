<x-admin.layout>
    <h1 class="text-2xl font-bold mb-4">Nouvelle page</h1>

    <form action="{{ route('admin.pages.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf

        <div>
            <label class="block font-semibold">Titre</label>
            <input type="text" name="title" value="{{ old('title') }}" class="border w-full p-2 rounded">
            @error('title') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block font-semibold">Contenu</label>
            <textarea name="content" rows="10" class="border w-full p-2 rounded">{{ old('content') }}</textarea>
            @error('content') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block font-semibold">Image (optionnelle)</label>
            <input type="file" name="image" class="border w-full p-2 rounded">
        </div>

        <div>
            <label>
                <input type="checkbox" name="is_published" value="1"> Publier immédiatement
            </label>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.pages.index') }}" class="btn btn-ghost">Annuler</a>
            <x-admin.button type="submit">Créer</x-admin.button>
        </div>
    </form>
</x-admin.layout>