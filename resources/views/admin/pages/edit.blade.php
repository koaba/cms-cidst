<x-admin.layout>
    <h1 class="text-2xl font-bold mb-4">Modifier la page</h1>

    <form action="{{ route('admin.pages.update', $page) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf @method('PUT')

        <div>
            <label class="block font-semibold">Titre</label>
            <input type="text" name="title" value="{{ old('title', $page->title) }}" class="border w-full p-2 rounded">
        </div>

        <div>
            <label class="block font-semibold">Contenu</label>
            <textarea name="content" rows="10" class="border w-full p-2 rounded">{{ old('content', $page->content) }}</textarea>
        </div>

        @if($page->image)
            <img src="{{ Storage::url($page->image) }}" class="w-32 mb-2">
        @endif

        <div>
            <label class="block font-semibold">Nouvelle image (remplace l'existante)</label>
            <input type="file" name="image" class="border w-full p-2 rounded">
        </div>

        <div>
            <label>
                <input type="checkbox" name="is_published" value="1" {{ $page->is_published ? 'checked' : '' }}> Publiée
            </label>
        </div>

        <button class="bg-blue-600 text-white px-4 py-2 rounded">Mettre à jour</button>
    </form>
</x-admin.layout>