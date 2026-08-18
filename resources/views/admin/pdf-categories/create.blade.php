<x-admin.layout>
    <h1 class="text-2xl font-bold mb-6">Nouvelle catégorie de documents PDF</h1>
    <form action="{{ route('admin.pdf-categories.store') }}" method="POST" class="max-w-lg space-y-4">
        @csrf
        <div>
            <label class="block font-medium mb-1">Nom *</label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="w-full border rounded p-2">
            @error('name') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>
        <div class="flex items-center gap-3">
            <x-admin.button type="submit">Créer</x-admin.button>
            <a href="{{ route('admin.pdf-categories.index') }}" class="btn btn-ghost">Annuler</a>
        </div>
    </form>
</x-admin.layout>