<x-admin.layout>
    <h1 class="text-2xl font-bold mb-6">Nouveau document PDF</h1>
    <form action="{{ route('admin.pdf-documents.store') }}" method="POST" enctype="multipart/form-data" class="max-w-lg space-y-4">
        @csrf
        <div>
            <label class="block font-medium mb-1">Titre *</label>
            <input type="text" name="title" value="{{ old('title') }}" required
                   class="w-full border rounded p-2">
            @error('title') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block font-medium mb-1">Description</label>
            <textarea name="description" rows="4" class="w-full border rounded p-2">{{ old('description') }}</textarea>
            @error('description') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block font-medium mb-1">Catégorie *</label>
            <select name="pdf_category_id" required class="w-full border rounded p-2">
                <option value="">— Choisir —</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ old('pdf_category_id') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
            @error('pdf_category_id') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block font-medium mb-1">Documents PDF *</label>
            <input type="file" name="pdfs[]" id="pdfs" accept="application/pdf" multiple
                   class="w-full border rounded p-2">
            <p class="text-xs text-gray-500 mt-1">Au moins 1 fichier requis, 10 max, 10 Mo max chacun.</p>
            @error('pdfs') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
            <ul id="pdfs-preview" class="text-sm mt-2 space-y-1"></ul>
        </div>
        <div class="flex items-center gap-3">
            <x-admin.button type="submit">Créer</x-admin.button>
            <a href="{{ route('admin.pdf-documents.index') }}" class="btn btn-ghost">Annuler</a>
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