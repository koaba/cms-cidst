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

    <form method="POST" action="{{ route('admin.articles.update', $article) }}" enctype="multipart/form-data" class="bg-white shadow rounded p-6 max-w-2xl">
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
                Publié
            </label>
        </div>

        <div class="mb-4">
            <label class="block font-medium mb-1">Image</label>
            @if ($article->image)
                <img src="{{ Storage::url($article->image) }}" alt="Image actuelle" class="w-32 h-32 object-cover rounded mb-2">
            @endif
            <input type="file" name="image" class="w-full border rounded p-2">
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Enregistrer</button>
        <a href="{{ route('admin.articles.index') }}" class="ml-2 text-gray-600">Annuler</a>
    </form>
</x-admin.layout>