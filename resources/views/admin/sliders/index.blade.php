<x-admin.layout>
    <h1 class="text-2xl font-bold mb-4">Sliders</h1>

    @if($sliders->count() >= \App\Http\Controllers\Admin\SliderController::MAX_SLIDERS)
        <p class="text-sm text-amber-600 mt-2">Limite de {{ \App\Http\Controllers\Admin\SliderController::MAX_SLIDERS }} sliders atteinte — supprimez-en un pour en ajouter un nouveau.</p>
    @else
        <x-admin.button href="{{ route('admin.sliders.create') }}">+ Nouveau slider</x-admin.button>
    @endif

    @if(session('success'))
        <p class="text-green-600 mt-2">{{ session('success') }}</p>
    @endif
    <table class="w-full mt-4 border-collapse">
        <thead>
            <tr class="border-b">
                <th class="text-left p-2">Image</th>
                <th class="text-left p-2">Titre</th>
                <th class="text-left p-2">Ordre</th>
                <th class="text-left p-2">Statut</th>
                <th class="text-left p-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sliders as $slider)
                <tr class="border-b">
                    <td class="p-2"><img src="{{ Storage::url($slider->image) }}" class="w-20 h-12 object-cover"></td>
                    <td class="p-2">{{ $slider->title }}</td>
                    <td class="p-2">{{ $slider->order }}</td>
                    <td class="p-2">{{ $slider->is_active ? 'Actif' : 'Inactif' }}</td>
                    <td class="p-2 space-x-2">
                        <a href="{{ route('admin.sliders.edit', $slider) }}" class="text-blue-600">Modifier</a>
                        <form action="{{ route('admin.sliders.destroy', $slider) }}" method="POST" class="inline">
                            @csrf @method('DELETE')
                            <button class="text-red-600" onclick="return confirm('Supprimer ?')">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-admin.layout>