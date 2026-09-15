{{--
    resources/views/pages/blocks/_pdf.blade.php

    Variables reçues via @include('pages.blocks._' . $block->type, ['data' => ..., 'media' => ...])
    dans resources/views/public/pages/show.blade.php :
    - $data  : array (contient 'pdf_document_id' et éventuellement 'title' pour le bloc)
    - $media : non utilisé ici (le bloc pdf ne stocke pas de médias directement,
               juste une référence vers un PdfDocument existant)

    Pas de visionneuse intégrée (décision actée : SEO / poids de chargement / responsive).
--}}

@php
    $pdfDocument = \App\Models\PdfDocument::with('pdfs')->find($data['pdf_document_id'] ?? null);
@endphp

@if($pdfDocument)
    <div class="pdf-block">
        @if(!empty($data['title']))
            <h2 class="text-2xl font-semibold mb-3">{{ $data['title'] }}</h2>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            @foreach($pdfDocument->pdfs as $pdf)
                <div class="border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                    @if(!empty($pdf->thumbnail_path))
                        <img
                            src="{{ Storage::url($pdf->thumbnail_path) }}"
                            alt="Miniature de {{ $pdf->original_name ?? $pdfDocument->title }}"
                            class="w-full h-40 object-cover bg-gray-100"
                        >
                    @else
                        <div class="w-full h-40 flex items-center justify-center bg-gray-100 text-gray-400">
                            PDF
                        </div>
                    @endif

                    <div class="p-3">
                        <p class="font-medium truncate" title="{{ $pdf->original_name ?? $pdfDocument->title }}">
                            {{ $pdf->original_name ?? $pdfDocument->title }}
                        </p>
                        @if(!empty($pdf->size))
                            <p class="text-sm text-gray-500 mb-2">
                                {{ number_format($pdf->size / 1024 / 1024, 2) }} Mo
                            </p>
                        @endif
                        <a
                            href="{{ Storage::url($pdf->path) }}"
                            download
                            class="inline-block text-sm text-blue-600 hover:text-blue-800 font-medium"
                        >
                            &darr; Télécharger
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
{{-- Si $pdfDocument est null (document supprimé entre-temps), le bloc n'affiche rien
     plutôt que de lever une erreur -- cohérent avec la décision "le bloc ne supprime
     jamais le PdfDocument référencé", mais un document peut disparaître pour d'autres
     raisons (suppression manuelle depuis Admin > Documents PDF). --}}