{{--
    resources/views/admin/pages/blocks/partials/_pdf.blade.php

    Variables attendues (fournies par PageBlockController::create()/edit()) :
    - $data          : array   -> valeurs actuelles du bloc (vide à la création)
    - $pdfDocuments  : Collection<PdfDocument> -> liste complète, pour le <select> "existant"

    Pattern de toggle JS repris de _video.blade.php (source_type) : deux blocs
    conditionnels affichés/masqués en fonction du radio "pdf_source".
--}}

@php
    $pdfSource = old('pdf_source', $data['pdf_source'] ?? 'existing');
@endphp

<div class="pdf-block-fields space-y-4">

    {{-- Choix de la source --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Source du document</label>
        <div class="flex gap-4">
            <label class="inline-flex items-center gap-2">
                <input
                    type="radio"
                    name="pdf_source"
                    value="existing"
                    {{ $pdfSource === 'existing' ? 'checked' : '' }}
                    onchange="toggleShowPdfSource(this)"
                >
                <span>Sélectionner un document existant</span>
            </label>
            <label class="inline-flex items-center gap-2">
                <input
                    type="radio"
                    name="pdf_source"
                    value="new"
                    {{ $pdfSource === 'new' ? 'checked' : '' }}
                    onchange="toggleShowPdfSource(this)"
                >
                <span>Créer un nouveau document</span>
            </label>
        </div>
        @error('pdf_source')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Bloc "document existant" --}}
    <div id="pdf-source-existing" class="{{ $pdfSource === 'existing' ? '' : 'hidden' }}">
        <label class="block text-sm font-medium text-gray-700 mb-1" for="pdf_document_id">
            Document PDF
        </label>
        <select
            name="pdf_document_id"
            id="pdf_document_id"
            class="w-full rounded border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
        >
            <option value="">-- Choisir --</option>
            @foreach($pdfDocuments as $document)
                <option
                    value="{{ $document->id }}"
                    {{ (string) old('pdf_document_id', $data['pdf_document_id'] ?? '') === (string) $document->id ? 'selected' : '' }}
                >
                    {{ $document->title }}
                </option>
            @endforeach
        </select>
        @error('pdf_document_id')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Bloc "nouveau document" --}}
    <div id="pdf-source-new" class="{{ $pdfSource === 'new' ? '' : 'hidden' }} space-y-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1" for="pdf_title">
                Titre du document
            </label>
            <input
                type="text"
                name="pdf_title"
                id="pdf_title"
                value="{{ old('pdf_title', $data['pdf_title'] ?? '') }}"
                class="w-full rounded border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
            >
            @error('pdf_title')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1" for="pdfs">
                Fichier(s) PDF
            </label>
            <input
                type="file"
                name="pdfs[]"
                id="pdfs"
                multiple
                accept="application/pdf"
                class="w-full text-sm text-gray-600"
            >
            <p class="text-xs text-gray-500 mt-1">
                Max {{ config('media.max_pdfs', 10) }} fichiers,
                {{ config('media.max_pdf_upload_kb', 10240) / 1024 }} Mo chacun.
            </p>
            @error('pdfs')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
            @error('pdfs.*')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

</div>

<script>
   function toggleShowPdfSource(radio) {
    const existing = document.getElementById('pdf-source-existing');
    const fresh = document.getElementById('pdf-source-new');
    if (radio.value === 'existing') {
        existing.classList.remove('hidden');
        fresh.classList.add('hidden');
    } else {
        existing.classList.add('hidden');
        fresh.classList.remove('hidden');
    }
}
</script>