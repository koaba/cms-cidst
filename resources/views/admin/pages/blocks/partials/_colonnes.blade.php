<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Titre de la section (optionnel)</label>
    <input type="text" name="title" value="{{ old('title', $data['title'] ?? '') }}"
           class="w-full border rounded p-2 text-sm">
    @error('title')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Nombre de colonnes</label>

    @if(isset($block))
        <input type="hidden" name="column_count" value="{{ $data['column_count'] ?? 2 }}">
        <p class="text-sm">{{ $data['column_count'] ?? 2 }} colonnes</p>
        <p class="text-xs text-gray-500 mt-1">
            Le nombre de colonnes ne peut plus être modifié une fois le bloc créé, pour ne pas perdre le contenu déjà ajouté. Créez un nouveau bloc « Colonnes multiples » si vous avez besoin d'une autre disposition.
        </p>
    @else
        <select name="column_count" class="w-full border rounded p-2 text-sm">
            @for($n = 2; $n <= 6; $n++)
                <option value="{{ $n }}" @selected((int) old('column_count', 2) === $n)>{{ $n }}</option>
            @endfor
        </select>
    @endif

    @error('column_count')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

@if(isset($block))
    {{-- Une seule requête pour tous les enfants + médias, groupés par colonne.
         Remplace les appels répétés à $block->childrenByColumn($i)->get()
         qui généraient 1 requête par colonne (voir passation §6). --}}
    @php $childrenByColumn = $block->childrenGroupedByColumn(); @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
        @for($i = 0; $i < ($data['column_count'] ?? 2); $i++)
            <div class="border rounded p-4">
                <h3 class="font-semibold text-sm mb-3">Colonne {{ $i + 1 }}</h3>

                <div class="space-y-2 mb-3">
                    @forelse($childrenByColumn->get($i, collect()) as $child)
                        <div class="flex justify-between items-center text-sm border rounded p-2">
                            <span>{{ config('page_blocks.types')[$child->type] ?? $child->type }}</span>
                            <div class="space-x-2">
                                <a href="{{ route('admin.pages.blocks.columns.edit', [$page, $block->id, $i, $child->id]) }}"
                                   class="text-blue-600">Modifier</a>
                                <form action="{{ route('admin.pages.blocks.columns.destroy', [$page, $block->id, $i, $child->id]) }}"
                                      method="POST" class="inline" onsubmit="return confirm('Supprimer ce bloc ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600">Supprimer</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400">Aucun bloc dans cette colonne.</p>
                    @endforelse
                </div>

                <p class="text-xs text-gray-500 mb-1">Ajouter :</p>
                <div class="flex flex-wrap gap-1">
                    @foreach(config('page_blocks.nestable_in_columns', []) as $nestableType)
                        <a href="{{ route('admin.pages.blocks.columns.create', [$page, $block->id, $i, $nestableType]) }}"
                           class="text-xs border rounded px-2 py-1 bg-gray-100 hover:bg-gray-200">
                            + {{ config('page_blocks.types')[$nestableType] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endfor
    </div>
@else
    <p class="text-sm text-gray-500 mt-4">Enregistrez d'abord ce bloc pour pouvoir ajouter du contenu dans les colonnes.</p>
@endif