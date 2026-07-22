<x-admin.layout>
    <h1 class="text-2xl font-bold mb-4">Pages</h1>

    <x-admin.button href="{{ route('admin.pages.create') }}">+ Nouvelle page</x-admin.button>

    @if(session('success'))
        <p class="text-green-600 mt-2">{{ session('success') }}</p>
    @endif

    <table class="w-full mt-4 border-collapse">
        <thead>
            <tr class="border-b">
                <th class="text-left p-2">Titre</th>
                <th class="text-left p-2">Statut</th>
                <th class="text-left p-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pages as $page)
                <tr class="border-b">
                    <td class="p-2">{{ $page->title }}</td>
                    <td class="p-2">{{ $page->is_published ? 'Publiée' : 'Brouillon' }}</td>
                    <td class="p-2 space-x-2">
                        <a href="{{ route('admin.pages.edit', $page) }}" class="text-blue-600">Modifier</a>
                        <form action="{{ route('admin.pages.destroy', $page) }}" method="POST" class="inline">
                            @csrf @method('DELETE')
                            <button class="text-red-600" onclick="return confirm('Supprimer ?')">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $pages->links() }}
</x-admin.layout>