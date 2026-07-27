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
            <label class="block font-medium mb-1">Date de publication</label>
            <input type="datetime-local" name="published_at" value="{{ old('published_at', $article->published_at?->format('Y-m-d\TH:i')) }}" class="w-full border rounded p-2">
            <p class="text-xs text-gray-500 mt-1">Modifie cette date pour antidater ou programmer la publication.</p>
        </div>

        <div class="mb-4">
            <label class="block font-medium mb-1">Image à la une</label>
            <input type="file" name="image" accept="image/*" class="w-full border rounded p-2">
        </div>

        <div class="mb-4">
            <label class="flex items-center gap-2">
                <input type="checkbox" id="toggle-gallery"
                       {{ $article->images->isNotEmpty() ? 'checked' : '' }}
                       onchange="document.getElementById('gallery-field').classList.toggle('hidden', !this.checked)">
                Galerie d'images
            </label>
            <div id="gallery-field" class="{{ $article->images->isNotEmpty() ? '' : 'hidden' }} mt-2">
                @if ($article->images->isNotEmpty())
                    <div class="flex flex-wrap gap-3 mb-3">
                        @foreach ($article->images as $image)
                            <label class="block w-24">
                                <img src="{{ Storage::url($image->path) }}" class="w-24 h-24 object-cover rounded border">
                                <span class="flex items-center gap-1 text-xs mt-1">
                                    <input type="checkbox" name="delete_images[]" value="{{ $image->id }}">
                                    Supprimer
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
                <input type="file" name="images[]" accept="image/*" multiple class="w-full border rounded p-2">
                <p class="text-xs text-gray-500 mt-1">15 images maximum au total, JPEG/PNG/WebP, 4 Mo max par image.</p>
            </div>
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