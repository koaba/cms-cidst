<x-admin.layout :title="'Blocs de contenu — '.$page->title">
    <h1 class="text-2xl font-bold mb-4">Blocs de contenu — {{ $page->title }}</h1>

    <a href="{{ route('admin.pages.builder.show', $page) }}" class="group inline-flex w-full items-center justify-center gap-2 rounded-lg bg-cidst-red px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-cidst-red/90 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-cidst-red/40 focus:ring-offset-2 md:float-right md:w-auto">Ouvrir le Page Builder <span aria-hidden="true" class="transition-transform group-hover:translate-x-0.5">&rarr;</span></a>
    <a href="{{ route('admin.pages.edit', $page) }}" class="text-sm text-base-content/60">&larr; Retour à la page</a>

    @if(session('success'))
        <p class="text-green-600 mt-2">{{ session('success') }}</p>
    @endif
    @error('delete_code')<div class="alert alert-error mt-2">{{ $message }}</div>@enderror

    <div class="mt-4 mb-6">
        <label for="add-block-type" class="text-sm font-medium mr-2">Ajouter un bloc :</label>
        <select id="add-block-type" class="border rounded p-2 text-sm">
            <option value="">-- Choisir un type --</option>
            @foreach($types as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
         <button type="button" class="text-sm border rounded px-3 py-2 bg-base-200 hover:bg-base-300 ml-2"
            onclick="const t = document.getElementById('add-block-type').value; if (t) { window.location = '{{ route('admin.pages.blocks.create', [$page, '__TYPE__']) }}'.replace('__TYPE__', t); } else { alert('Choisis d\'abord un type de bloc dans la liste.'); }">
            + Ajouter
        </button>
    </div>

     @vite(['resources/js/admin/page-blocks-reorder.js'])

    <div data-page-blocks-reorder data-reorder-url="{{ route('admin.pages.blocks.reorder', $page) }}" class="space-y-2">
        @forelse($blocks as $block)
            <div data-block-id="{{ $block->id }}" class="border rounded p-3 flex justify-between items-center cursor-move bg-cidst-surface">
                <div>
                    <span class="font-medium">{{ $types[$block->type] ?? $block->type }}</span>
                    @if(!empty($block->data['title']))
                        <span class="text-sm text-base-content/60 ml-2">{{ $block->data['title'] }}</span>
                    @endif
                </div>
                <div class="space-x-2">
                    <a href="{{ route('admin.pages.blocks.edit', [$page, $block->id]) }}" class="text-blue-600 text-sm">Modifier</a>
                    <x-delete-form :action="route('admin.pages.blocks.destroy', [$page, $block->id])" message="Supprimer ce bloc ?">
                        <button type="submit" class="text-red-600 text-sm">Supprimer</button>
                    </x-delete-form>
                </div>
            </div>
        @empty
            <p class="text-base-content/60">Aucun bloc pour l'instant.</p>
        @endforelse
    </div>
</x-admin.layout>