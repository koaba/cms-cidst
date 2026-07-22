<x-admin.layout>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Articles</h1>
        <x-admin.button href="{{ route('admin.articles.create') }}">+ Nouvel article</x-admin.button>
    </div>

    @if (session('success'))
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <table class="w-full bg-white shadow rounded">
        <thead>
            <tr class="text-left border-b">
                <th class="p-3">Titre</th>
                <th class="p-3">Auteur</th>
                <th class="p-3">Statut</th>
                <th class="p-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($articles as $article)
                <tr class="border-b">
                    <td class="p-3">{{ $article->title }}</td>
                    <td class="p-3">{{ $article->user->name }}</td>
                    <td class="p-3">
                        {{ $article->is_published ? 'Publié' : 'Brouillon' }}
                    </td>
                    <td class="p-3 flex gap-2">
                        <a href="{{ route('admin.articles.edit', $article) }}" class="text-blue-600">Modifier</a>
                        <form method="POST" action="{{ route('admin.articles.destroy', $article) }}" onsubmit="return confirm('Supprimer cet article ?')">
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
        {{ $articles->links() }}
    </div>
</x-admin.layout>