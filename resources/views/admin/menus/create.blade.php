<x-admin.layout>
    <h1 class="text-2xl font-bold mb-6">Nouveau menu</h1>

    <form action="{{ route('admin.menus.store') }}" method="POST" class="max-w-lg space-y-4">
        @csrf

        <div>
            <label class="block font-medium mb-1">Libellé *</label>
            <input type="text" name="label" value="{{ old('label') }}" required
                   class="w-full border rounded p-2">
            @error('label') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block font-medium mb-1">Cible (route nommée ou URL) *</label>
            <input type="text" name="target" value="{{ old('target') }}" required
                   placeholder="ex: blog.index ou /contact ou https://..."
                   class="w-full border rounded p-2">
            <p class="text-xs text-gray-500 mt-1">Nom de route Laravel (ex: blog.index) ou URL libre (ex: /contact, https://...)</p>
            @error('target') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
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