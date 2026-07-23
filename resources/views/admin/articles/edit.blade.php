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
                Publier immédiatement
            </label>
        </div>

        <div class="mb-4">
            <label class="block font-medium mb-1">Image</label>
            <input type="file" name="image" class="w-full border rounded p-2">
        </div>

        <div class="mb-4">
            <label class="flex items-center gap-2">
                <input type="checkbox" id="toggle-categories"
                       {{ $article->categories->isNotEmpty() ? 'checked' : '' }}
                       onchange="document.getElementById('categories-field').classList.toggle('hidden', !this.checked)">
                Ajouter des catégories
            </label>
            <div id="categories-field" class="{{ $article->categories->isNotEmpty() ? '' : 'hidden' }} mt-2 flex flex-wrap gap-3">
                @foreach ($categories as $category)
                    <label class="flex items-center gap-1">
                        <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                               {{ $article->categories->contains($category->id) ? 'checked' : '' }}>
                        {{ $category->name }}
                    </label>
                @endforeach
            </div>
        </div>

        <x-admin.button type="submit">Mettre à jour</x-admin.button>
        <a href="{{ route('admin.articles.index') }}" class="ml-2 text-gray-600">Annuler</a>
    </form>
</x-admin.layout>