<x-admin.layout>
    <h1 class="text-2xl font-bold mb-4">Pages</h1>

    <x-admin.button href="{{ route('admin.pages.create') }}">+ Nouvelle page</x-admin.button>

    @if(session('success'))
        <p class="text-green-600 mt-2">{{ session('success') }}</p>
    @endif

    @error('delete_code')<div class="alert alert-error mb-4">{{ $message }}</div>@enderror
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
                    <td class="p-2">
                        @php
                            [$badgeClass, $badgeLabel] = match ($page->status) {
                                "published" => ["badge-success", "Publi\u{E9}e"],
                                "scheduled" => ["badge-warning", "Programm\u{E9}e"],
                                default => ["badge-ghost", "Brouillon"],
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                    </td>
                    <td class="p-2 space-x-2">
                        <a href="{{ route('admin.pages.edit', $page) }}" class="text-blue-600">Modifier</a>
                        <a href="{{ route('admin.pages.blocks.index', $page) }}" class="text-purple-600">Blocs</a>
                        <form action="{{ route('admin.pages.destroy', $page) }}" method="POST" class="inline">
                            @csrf @method('DELETE')
                            @unless (auth()->user()->hasRole('Super Admin'))<input type="hidden" name="delete_code">@endunless
                            <button class="text-red-600" onclick="return confirm('Supprimer ?') && (!this.form.delete_code || !!(this.form.delete_code.value = prompt('Code de suppression') || ''))">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $pages->links() }}
</x-admin.layout>