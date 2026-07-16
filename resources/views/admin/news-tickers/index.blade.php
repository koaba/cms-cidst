<x-admin.layout>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Bandeau d'actualités</h1>
        <a href="{{ route('admin.news-tickers.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded">
            + Nouvelle actualité
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <table class="w-full border-collapse">
        <thead>
            <tr class="text-left border-b">
                <th class="p-2">Contenu</th>
                <th class="p-2">Lien</th>
                <th class="p-2">Ordre</th>
                <th class="p-2">Actif</th>
                <th class="p-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($newsTickers as $item)
            <tr class="border-b">
                <td class="p-2">{{ Str::limit($item->content, 60) }}</td>
                <td class="p-2">{{ $item->link_url ?? '—' }}</td>
                <td class="p-2">{{ $item->order }}</td>
                <td class="p-2">
                    <span class="{{ $item->is_active ? 'text-green-600' : 'text-gray-400' }}">
                        {{ $item->is_active ? 'Oui' : 'Non' }}
                    </span>
                </td>
                <td class="p-2 flex gap-2">
                    <a href="{{ route('admin.news-tickers.edit', $item) }}" class="text-blue-600">Modifier</a>
                    <form action="{{ route('admin.news-tickers.destroy', $item) }}" method="POST"
                          onsubmit="return confirm('Supprimer cette actualité ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600">Supprimer</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="p-4 text-center text-gray-500">Aucune actualité</td></tr>
            @endforelse
        </tbody>
    </table>
</x-admin.layout>