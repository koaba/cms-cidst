<x-admin.layout>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Catégories</h1>
        <x-admin.button href="{{ route('admin.categories.create') }}">+ Nouvelle catégorie</x-admin.button>
    </div>
    @if (session('success'))
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif
    <table class="w-full bg-white shadow rounded">
        <thead>
            <tr class="text-left border-b">
                <th class="p-3">Nom</th>
                <th class="p-3">Articles</th>
                <th class="p-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($categories as $category)
                <tr class="border-b">
                    <td class="p-3">{{ $category->name }}</td>
                    <td class="p-3">{{ $category->articles_count }}</td>
                    <td class="p-3 flex gap-2">
                        <a href="{{ route('admin.categories.edit', $category) }}" class="text-blue-600">Modifier</a>
                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Supprimer cette catégorie ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="mt-4">
        {{ $categories->links() }}
    </div>
</x-admin.layout>