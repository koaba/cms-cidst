<x-admin.layout>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Menus de navigation</h1>
        <x-admin.button href="{{ route('admin.menus.create') }}">+ Nouveau menu</x-admin.button>
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
                <x-menu-row :menu="$menu" :depth="0" />
            @empty
                <tr><td colspan="5" class="p-4 text-center text-gray-500">Aucun menu</td></tr>
            @endforelse
        </tbody>
    </table>
</x-admin.layout>