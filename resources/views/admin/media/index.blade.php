<x-admin.layout title="Médiathèque">
    <h1 class="text-2xl font-bold mb-6">Médiathèque</h1>

    <form action="{{ route('admin.media.index') }}" method="GET" class="mb-6">
        <div class="flex gap-2 max-w-md">
            <input
                type="text"
                name="q"
                value="{{ $search }}"
                placeholder="Rechercher par nom de fichier..."
                class="flex-1 border rounded px-4 py-2"
            >
            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700">
                Rechercher
            </button>
        </div>
    </form>

    @if ($media->isEmpty())
        <p class="text-gray-500">Aucun média trouvé.</p>
    @else
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach ($media as $item)
                <div class="border rounded overflow-hidden bg-white">
                    <img src="{{ asset('storage/' . $item->path) }}" alt="{{ $item->original_name }}" class="w-full h-32 object-cover">
                    <div class="p-2">
                        <p class="text-sm font-medium truncate" title="{{ $item->original_name }}">
                            {{ $item->original_name }}
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ number_format($item->size / 1024, 0) }} Ko
                        </p>
                        @php $usages = $item->usages(); @endphp
                        @if (count($usages) > 0)
                            <p class="text-xs text-gray-600 mt-1">
                                Utilisé par :
                                @foreach ($usages as $usage)
                                    {{ $usage['type'] }} « {{ $usage['title'] }} »@if (!$loop->last), @endif
                                @endforeach
                            </p>
                        @else
                            <p class="text-xs text-orange-600 mt-1">Non utilisé</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $media->links() }}
        </div>
    @endif
</x-admin.layout>