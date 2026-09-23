<x-admin.layout>
    <h1 class="text-2xl font-bold mb-4">
        Ajouter du contenu — {{ $item->data['title'] }} — {{ $page->title }}
    </h1>

    <form action="{{ route('admin.pages.blocks.items.content.store', [$page, $parent->id, $item->id]) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">

        @include('admin.pages.blocks.partials._' . $type)

        <div class="mt-4 space-x-2">
            <button type="submit" class="text-sm border rounded px-3 py-2 bg-blue-600 text-white hover:bg-blue-700">Enregistrer</button>
            <a href="{{ route('admin.pages.blocks.items.edit', [$page, $parent->id, $item->id]) }}" class="text-sm text-gray-500">Annuler</a>
        </div>
    </form>
</x-admin.layout>