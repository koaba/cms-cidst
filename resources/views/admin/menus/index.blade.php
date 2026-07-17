<x-admin.layout>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Menus de navigation</h1>
        <a href="{{ route('admin.menus.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded">
            + Nouveau menu
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <table class="w-full border-collapse">
        <thead>
            <tr class="text-left border-b">
                <th class="p-2">Libellé</th>
                <th class="p-2">Cible</th>
                <th class="p-2">Ordre</th>
                <th class="p-2">Actif</th>
                <th class="p-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($menus as $menu)
            <tr class="border-b">
                <td class="p-2">{{ $menu->label }}</td>
                <td class="p-2 text-sm text-gray-500">{{ $menu->target }}</td>
                <td class="p-2">{{ $menu->order }}</td>
                <td class="p-2">
                    <span class="{{ $menu->is_active ? 'text-green-600' : 'text-gray-400' }}">
                        {{ $menu->is_active ? 'Oui' : 'Non' }}
                    </span>
                </td>
                <td class="p-2 flex gap-2">
                    <a href="{{ route('admin.menus.edit', $menu) }}" class="text-blue-600">Modifier</a>
                    <form action="{{ route('admin.menus.destroy', $menu) }}" method="POST"
                          onsubmit="return confirm('Supprimer ce menu ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600">Supprimer</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="p-4 text-center text-gray-500">Aucun menu</td></tr>
            @endforelse
        </tbody>
    </table>
</x-admin.layout>