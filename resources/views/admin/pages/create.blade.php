<x-admin.layout>
    <h1 class="text-2xl font-bold mb-4">Nouvelle page</h1>

    <form action="{{ route('admin.pages.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf

    <div>
            <label class="block font-semibold">Titre</label>
            <input type="text" name="title" value="{{ old('title') }}" class="border w-full p-2 rounded" required>
            @error('title') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

       <div>
            <label class="block font-semibold">Contenu</label>
            <textarea name="content" rows="10" class="border w-full p-2 rounded" required>{{ old('content') }}</textarea>
            @error('content') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block font-semibold">Date de publication</label>
            <input type="date" name="published_at" value="{{ old('published_at') }}" class="border w-full p-2 rounded" required>
            @error('published_at') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block font-semibold">Image à la une</label>
            <input type="file" name="image" class="border w-full p-2 rounded" required>
            @error('image') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

      <div>
            <label>
                <input type="checkbox" name="is_published" value="1" {{ old('is_published') ? 'checked' : '' }}> Publier immédiatement
            </label>
        </div>

        @include('admin.partials.seo-fields', ['model' => null])

        <div class="flex items-center gap-3">
            <x-admin.button type="submit">Créer</x-admin.button>
            <a href="{{ route('admin.pages.index') }}" class="btn btn-ghost">Annuler</a>
        </div>
    </form>
</x-admin.layout>