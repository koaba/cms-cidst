<x-admin.layout>
    <h1 class="text-2xl font-bold mb-4">Modifier un bloc — {{ $page->title }}</h1>

    <form action="{{ route('admin.pages.blocks.update', [$page, $block->id]) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @php($type = $block->type)
        @include('admin.pages.blocks.partials._' . $type, ['data' => $block->data])

        <div class="mt-4 space-x-2">
            <button type="submit" class="text-sm border rounded px-3 py-2 bg-blue-600 text-white hover:bg-blue-700">Enregistrer</button>
            <a href="{{ route('admin.pages.blocks.index', $page) }}" class="text-sm text-gray-500">Annuler</a>
        </div>
    </form>

    @if($block->type === 'colonnes')
        @php($childrenByColumn = $block->childrenGroupedByColumn())
        @php($nestableTypes = config('page_blocks.nestable_in_columns'))

        <div class="mt-8 border-t pt-6">
            <h2 class="text-lg font-semibold mb-4">Contenu des colonnes</h2>

            <div class="grid gap-4" style="grid-template-columns: repeat({{ $block->data['column_count'] }}, minmax(0, 1fr));">
                @for($i = 0; $i < $block->data['column_count']; $i++)
                    <div class="border rounded p-3 bg-gray-50">
                        <p class="text-sm font-medium mb-2">Colonne {{ $i + 1 }}</p>

                        <ul class="space-y-2 mb-3">
                            @forelse($childrenByColumn->get($i, collect()) as $child)
                                <li class="flex items-center justify-between bg-white border rounded p-2 text-sm">
                                    <span>{{ config('page_blocks.types')[$child->type] ?? $child->type }}</span>
                                    <span class="space-x-2">
                                        <a href="{{ route('admin.pages.blocks.columns.edit', [$page, $block->id, $i, $child->id]) }}" class="text-blue-600 hover:underline">Modifier</a>
                                        <form action="{{ route('admin.pages.blocks.columns.destroy', [$page, $block->id, $i, $child->id]) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer ce bloc de la colonne ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Supprimer</button>
                                        </form>
                                    </span>
                                </li>
                            @empty
                                <li class="text-sm text-gray-400 italic">Colonne vide</li>
                            @endforelse
                        </ul>

                        <div class="flex flex-wrap gap-1">
                            @foreach($nestableTypes as $nestableType)
                                <a href="{{ route('admin.pages.blocks.columns.create', [$page, $block->id, $i, $nestableType]) }}"
                                   class="text-xs border rounded px-2 py-1 hover:bg-gray-100">
                                    + {{ config('page_blocks.types')[$nestableType] ?? $nestableType }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    @endif
</x-admin.layout>