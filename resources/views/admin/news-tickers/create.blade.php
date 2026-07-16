<x-admin.layout>
    <h1 class="text-2xl font-bold mb-6">Nouvelle actualité</h1>

    <form action="{{ route('admin.news-tickers.store') }}" method="POST" class="max-w-lg space-y-4">
        @csrf

        <div>
            <label class="block font-medium mb-1">Contenu *</label>
            <textarea name="content" rows="3" required
                      class="w-full border rounded p-2">{{ old('content') }}</textarea>
            @error('content') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block font-medium mb-1">Lien (optionnel)</label>
            <input type="url" name="link_url" value="{{ old('link_url') }}"
                   class="w-full border rounded p-2">
            @error('link_url') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block font-medium mb-1">Ordre</label>
            <input type="number" name="order" value="{{ old('order', 0) }}"
                   class="w-full border rounded p-2">
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_active" id="is_active" value="1"
                   {{ old('is_active', true) ? 'checked' : '' }}>
            <label for="is_active">Actif</label>
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Créer</button>
    </form>
</x-admin.layout>