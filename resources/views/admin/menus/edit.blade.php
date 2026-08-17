<x-admin.layout>
    <h1 class="text-2xl font-bold mb-6">Modifier le menu</h1>
    <form action="{{ route('admin.menus.update', $menu) }}" method="POST" enctype="multipart/form-data" class="max-w-lg space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label class="block font-medium mb-1">Libellé *</label>
            <input type="text" name="label" value="{{ old('label', $menu->label) }}" required
                   class="w-full border rounded p-2">
            @error('label') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block font-medium mb-1">Cible (route nommée ou URL) *</label>
            <input type="text" name="target" value="{{ old('target', $menu->target) }}" required
                   class="w-full border rounded p-2">
            @error('target') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block font-medium mb-1">Menu parent</label>
            <select name="parent_id" class="w-full border rounded p-2">
                <option value="">— Aucun (menu de premier niveau) —</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" {{ old('parent_id', $menu->parent_id) == $parent->id ? 'selected' : '' }}>
                        {{ $parent->label }}
                    </option>
                @endforeach
            </select>
            @error('parent_id') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block font-medium mb-1">Ordre</label>
            <input type="number" name="order" value="{{ old('order', $menu->order) }}"
                   class="w-full border rounded p-2">
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_active" id="is_active" value="1"
                   {{ old('is_active', $menu->is_active) ? 'checked' : '' }}>
            <label for="is_active">Actif</label>
        </div>

        <div>
            <label class="block font-medium mb-1">Documents PDF existants</label>
            @if($menu->pdfs->isEmpty())
                <p class="text-sm text-gray-500">Aucun document pour l'instant.</p>
            @else
                <ul class="space-y-1">
                    @foreach($menu->pdfs as $pdf)
                        <li class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="delete_pdfs[]" id="delete_pdf_{{ $pdf->id }}" value="{{ $pdf->id }}">
                            <label for="delete_pdf_{{ $pdf->id }}" class="text-red-600">Supprimer</label>
                            <a href="{{ $pdf->url ?? \Illuminate\Support\Facades\Storage::url($pdf->path) }}" target="_blank" class="underline">
                                📄 {{ $pdf->name ?? basename($pdf->path) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
                @error('delete_pdfs') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
            @endif
        </div>

        <div>
            <label class="block font-medium mb-1">Ajouter des documents PDF</label>
            <input type="file" name="pdfs[]" id="pdfs" accept="application/pdf" multiple
                   class="w-full border rounded p-2">
            <p class="text-xs text-gray-500 mt-1">10 fichiers max au total, 10 Mo max chacun.</p>
            @error('pdfs') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
            <ul id="pdfs-preview" class="text-sm mt-2 space-y-1"></ul>
        </div>

        <div class="flex items-center gap-3">
            <x-admin.button type="submit">Mettre à jour</x-admin.button>
            <a href="{{ route('admin.menus.index') }}" class="btn btn-ghost">Annuler</a>
        </div>
    </form>

    <script>
        document.getElementById('pdfs').addEventListener('change', function (e) {
            const list = document.getElementById('pdfs-preview');
            list.innerHTML = '';
            Array.from(e.target.files).forEach(file => {
                const li = document.createElement('li');
                li.textContent = `📄 ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} Mo)`;
                list.appendChild(li);
            });
        });
    </script>
</x-admin.layout>