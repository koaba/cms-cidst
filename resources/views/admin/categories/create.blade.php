<x-admin.layout>
    <h1 class="text-2xl font-bold mb-6">Nouvelle catégorie</h1>

    @if ($errors->any())
        <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.categories.store') }}" class="bg-white shadow rounded p-6 max-w-lg">
        @csrf

        <div class="mb-4">
            <label class="block font-medium mb-1">Nom</label>
            <input type="text" name="name" value="{{ old('name') }}" class="w-full border rounded p-2">
        </div>

        <div class="flex items-center gap-3">
            <x-admin.button type="submit">Créer</x-admin.button>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-ghost">Annuler</a>
        </div>
    </form>
</x-admin.layout>