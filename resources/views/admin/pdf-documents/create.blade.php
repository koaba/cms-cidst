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
            <div id="pdf-thumbnails-preview" class="flex flex-wrap gap-2 mb-2"></div>
            <input type="hidden" name="pdf_thumbnails" id="pdf-thumbnails-data">
            <p class="text-xs text-gray-500 mt-1">Au moins 1 fichier requis, 10 max, 10 Mo max chacun.</p>
                       @error('pdfs') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
            <ul id="pdfs-preview" class="text-sm mt-2 space-y-1"></ul>
            <label class="flex items-center gap-2 text-sm text-gray-700 mt-2">
                <input type="checkbox" name="apply_watermark" value="1">
                Appliquer le filigrane de protection sur ces documents
            </label>
        </div>
        <div class="flex items-center gap-3">
            <x-admin.button type="submit">Créer</x-admin.button>
            <a href="{{ route('admin.pdf-documents.index') }}" class="btn btn-ghost">Annuler</a>
        </div>
    </form>

       @push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        document.getElementById('pdfs')?.addEventListener('change', async function (e) {
            const files = Array.from(e.target.files);

            const list = document.getElementById('pdfs-preview');
            list.innerHTML = '';
            files.forEach(file => {
                const li = document.createElement('li');
                li.textContent = `📄 ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} Mo)`;
                list.appendChild(li);
            });

            const preview = document.getElementById('pdf-thumbnails-preview');
            const dataInput = document.getElementById('pdf-thumbnails-data');
            preview.innerHTML = '';
            const thumbnails = [];

            for (const file of files) {
                try {
                    const arrayBuffer = await file.arrayBuffer();
                    const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
                    const page = await pdf.getPage(1);
                    const viewport = page.getViewport({ scale: 0.5 });

                    const canvas = document.createElement('canvas');
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;
                    await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;

                    const dataUrl = canvas.toDataURL('image/jpeg', 0.7);
                    thumbnails.push({ name: file.name, thumbnail: dataUrl });

                    const img = document.createElement('img');
                    img.src = dataUrl;
                    img.className = 'w-16 h-20 object-cover rounded border';
                    preview.appendChild(img);
                } catch (err) {
                    console.error('Erreur génération miniature PDF pour', file.name, err);
                    thumbnails.push({ name: file.name, thumbnail: null });
                }
            }

            dataInput.value = JSON.stringify(thumbnails);
        });
    </script>
    @endpush
</x-admin.layout>