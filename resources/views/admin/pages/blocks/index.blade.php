<x-admin.layout>
    <h1 class="text-2xl font-bold mb-4">Blocs de contenu — {{ $page->title }}</h1>

    <a href="{{ route('admin.pages.edit', $page) }}" class="text-sm text-gray-500">&larr; Retour à la page</a>

    @if(session('success'))
        <p class="text-green-600 mt-2">{{ session('success') }}</p>
    @endif

    <div class="mt-4 mb-6">
        <label for="add-block-type" class="text-sm font-medium mr-2">Ajouter un bloc :</label>
        <select id="add-block-type" class="border rounded p-2 text-sm">
            <option value="">-- Choisir un type --</option>
            @foreach($types as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
         <button type="button" class="text-sm border rounded px-3 py-2 bg-gray-50 hover:bg-gray-100 ml-2"
            onclick="const t = document.getElementById('add-block-type').value; if (t) { window.location = '{{ route('admin.pages.blocks.create', [$page, '__TYPE__']) }}'.replace('__TYPE__', t); } else { alert('Choisis d\'abord un type de bloc dans la liste.'); }">
            + Ajouter
        </button>
    </div>

     @vite(['resources/js/admin/page-blocks-reorder.js'])

    <div data-page-blocks-reorder data-reorder-url="{{ route('admin.pages.blocks.reorder', $page) }}" class="space-y-2">
        @forelse($blocks as $block)
            <div data-block-id="{{ $block->id }}" class="border rounded p-3 flex justify-between items-center cursor-move bg-white">
                <div>
                    <span class="font-medium">{{ $types[$block->type] ?? $block->type }}</span>
                    @if(!empty($block->data['title']))
                        <span class="text-sm text-gray-500 ml-2">{{ $block->data['title'] }}</span>
                    @endif
                </div>
                <div class="space-x-2">
                    <a href="{{ route('admin.pages.blocks.edit', [$page, $block->id]) }}" class="text-blue-600 text-sm">Modifier</a>
                    <form action="{{ route('admin.pages.blocks.destroy', [$page, $block->id]) }}" method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button class="text-red-600 text-sm" onclick="return confirm('Supprimer ce bloc ?')">Supprimer</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-gray-500">Aucun bloc pour l'instant.</p>
        @endforelse
    </div>
</x-admin.layout>