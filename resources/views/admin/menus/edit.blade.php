<x-admin.layout>
    <h1 class="text-2xl font-bold mb-6">Modifier le menu</h1>
    <form action="{{ route('admin.menus.update', $menu) }}" method="POST" class="max-w-lg space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label class="block font-medium mb-1">Libellé *</label>
            <input type="text" name="label" value="{{ old('label', $menu->label) }}" required
                   class="w-full border rounded p-2">
            @error('label') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block font-medium mb-1">Cible (route nommée ou URL) *</label>
            <input type="text" name="target" value="{{ old('target', $menu->target) }}" required
                   class="w-full border rounded p-2">
            @error('target') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block font-medium mb-1">Menu parent</label>
            <select name="parent_id" class="w-full border rounded p-2">
                <option value="">— Aucun (menu de premier niveau) —</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" {{ old('parent_id', $menu->parent_id) == $parent->id ? 'selected' : '' }}>
                        {{ $parent->label }}
                    </option>
                @endforeach
            </select>
            @error('parent_id') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block font-medium mb-1">Ordre</label>
            <input type="number" name="order" value="{{ old('order', $menu->order) }}"
                   class="w-full border rounded p-2">
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_active" id="is_active" value="1"
                   {{ old('is_active', $menu->is_active) ? 'checked' : '' }}>
            <label for="is_active">Actif</label>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.menus.index') }}" class="btn btn-ghost">Annuler</a>
            <x-admin.button type="submit">Mettre à jour</x-admin.button>
        </div>
    </form>
</x-admin.layout>