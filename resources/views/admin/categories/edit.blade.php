<x-admin.layout>
    <h1 class="text-2xl font-bold mb-6">Modifier la catégorie</h1>

    @if ($errors->any())
        <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data" class="bg-white shadow rounded p-6 max-w-lg">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block font-medium mb-1">Nom</label>
            <input type="text" name="name" value="{{ old('name', $category->name) }}" class="w-full border rounded p-2">
        </div>

        <div class="mb-6 border-t pt-4">
            <h2 class="font-semibold mb-2">Documents PDF <span class="text-xs text-gray-500 font-normal">(10 max)</span></h2>

            @if ($category->pdfs->isNotEmpty())
                <div class="flex flex-col gap-1 mb-3">
                    @foreach ($category->pdfs as $pdf)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="delete_pdfs[]" value="{{ $pdf->id }}">
                            📄 {{ $pdf->original_name }}
                            <span class="text-xs text-gray-500">({{ round($pdf->size / 1024) }} Ko)</span>
                            <span class="text-xs text-red-600">Supprimer</span>
                        </label>
                    @endforeach
                </div>
            @endif

            <div id="pdfs-selected" class="flex flex-col gap-1 mb-2"></div>

            <label class="text-sm border rounded px-3 py-2 cursor-pointer bg-gray-50 hover:bg-gray-100 inline-block">
                + Uploader des PDF
                <input type="file" name="pdfs[]" accept="application/pdf" multiple class="hidden" onchange="previewPdfs(this, 'pdfs-selected')">
            </label>
        </div>

        <div class="flex items-center gap-3">
            <x-admin.button type="submit">Mettre à jour</x-admin.button>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-ghost">Annuler</a>
        </div>

    </form>

    <script>
        function previewPdfs(input, containerId) {
            const container = document.getElementById(containerId);
            [...input.files].forEach(file => {
                const chip = document.createElement('div');
                chip.className = 'text-xs bg-gray-100 border rounded px-2 py-1 inline-flex items-center gap-1 w-fit';
                chip.textContent = '📄 ' + file.name;
                container.appendChild(chip);
            });
        }
    </script>
</x-admin.layout>