<x-admin.layout title="Modifier un élément">
    <h1 class="text-2xl font-bold mb-4">
        Modifier l'item — {{ $page->title }}
    </h1>
    @error('delete_code')<div class="alert alert-error mb-4">{{ $message }}</div>@enderror

    <form action="{{ route('admin.pages.blocks.items.update', [$page, $parent->id, $item->id]) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Titre de l'item</label>
            <input type="text" name="title" value="{{ $item->data['title'] }}" class="w-full border rounded p-2 text-sm">
            @error('title')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mt-4 space-x-2">
            <button type="submit" class="text-sm border rounded px-3 py-2 bg-blue-600 text-white hover:bg-blue-700">Enregistrer</button>
            <a href="{{ route('admin.pages.blocks.edit', [$page, $parent->id]) }}" class="text-sm text-base-content/60">Retour</a>
        </div>
    </form>

    <div class="mt-8 border-t pt-6">
        <h2 class="text-lg font-semibold mb-4">Contenu de l'item</h2>

        <ul class="space-y-2 mb-3">
            @forelse($item->children as $content)
                <li class="flex items-center justify-between bg-cidst-surface border rounded p-2 text-sm">
                    <span>{{ config('page_blocks.types')[$content->type] ?? $content->type }}</span>
                    <span class="space-x-2">
                        <a href="{{ route('admin.pages.blocks.items.content.edit', [$page, $parent->id, $item->id, $content->id]) }}" class="text-blue-600 hover:underline">Modifier</a>
                        <x-delete-form :action="route('admin.pages.blocks.items.content.destroy', [$page, $parent->id, $item->id, $content->id])" message="Supprimer ce contenu ?">
                            <button type="submit" class="text-red-600 hover:underline">Supprimer</button>
                        </x-delete-form>
                    </span>
                </li>
            @empty
                <li class="text-sm text-base-content/50 italic">Aucun contenu pour cet item</li>
            @endforelse
        </ul>

        <div class="flex flex-wrap gap-1">
            @foreach(config('page_blocks.nestable_in_columns') as $nestableType)
                <a href="{{ route('admin.pages.blocks.items.content.create', [$page, $parent->id, $item->id, $nestableType]) }}"
                   class="text-xs border rounded px-2 py-1 hover:bg-base-300">
                    + {{ config('page_blocks.types')[$nestableType] ?? $nestableType }}
                </a>
            @endforeach
        </div>
    </div>
</x-admin.layout>