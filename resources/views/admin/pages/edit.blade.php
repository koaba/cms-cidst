<x-admin.layout>
    <h1 class="text-2xl font-bold mb-4">Modifier la page</h1>
    <form action="{{ route('admin.pages.update', $page) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf @method('PUT')
        <div>
            <label class="block font-semibold">Titre</label>
            <input type="text" name="title" value="{{ old('title', $page->title) }}" class="border w-full p-2 rounded" required>
            @error('title') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block font-semibold">Slug (URL)</label>
            <input type="text" name="slug" value="{{ old('slug', $page->slug) }}" class="border w-full p-2 rounded">
            <p class="text-gray-500 text-sm">Modifier le slug change l&rsquo;adresse de la page ; les anciens liens ne sont pas redirig&eacute;s.</p>
            @error('slug') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block font-semibold">Contenu</label>
            <textarea name="content" rows="10" class="border w-full p-2 rounded" required>{{ old('content', $page->content) }}</textarea>            @error('content') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block font-semibold">Date de publication</label>
            <input type="datetime-local" name="published_at" value="{{ old('published_at', $page->published_at?->format('Y-m-d\TH:i')) }}" class="border w-full p-2 rounded" required>
            @error('published_at') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>
        @if($page->media->isNotEmpty())
            <img src="{{ Storage::url($page->media->first()->path) }}" class="w-32 mb-2">
        @endif
        <div>
            <label class="block font-semibold">Nouvelle image (remplace l'existante, optionnelle)</label>
            <input type="file" name="image" class="border w-full p-2 rounded">
            @error('image') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>
        <div>
            <label>
                <input type="checkbox" name="is_published" value="1" {{ old('is_published', $page->is_published) ? 'checked' : '' }}> Publiée
            </label>
        </div>

        @include('admin.partials.seo-fields', ['model' => $page])

        <div class="flex items-center gap-3">
            <x-admin.button type="submit">Mettre à jour</x-admin.button>
            <a href="{{ route('admin.pages.index') }}" class="btn btn-ghost">Annuler</a>
        </div>
    </form>
</x-admin.layout>