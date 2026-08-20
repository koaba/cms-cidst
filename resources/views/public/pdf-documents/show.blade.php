<x-layout>
    <div class="max-w-2xl mx-auto">
        <span class="text-[10px] leading-none bg-cidst-red/10 text-cidst-red px-1.5 py-0.5 rounded-full">
            {{ $document->category->name }}
        </span>

        <h1 class="text-3xl font-display font-bold mt-3 mb-4 text-cidst-ink">
            {{ $document->title }}
        </h1>

        @if ($document->description)
            <p class="text-cidst-ink mb-6">
                {{ $document->description }}
            </p>
        @endif

        <h2 class="text-lg font-display font-semibold text-cidst-ink mb-3">Documents disponibles</h2>

        @if ($document->pdfs->isEmpty())
            <p class="text-cidst-muted">Aucun fichier disponible pour le moment.</p>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                @foreach ($document->pdfs as $pdf)
                    <a href="{{ Storage::url($pdf->path) }}" target="_blank"
                       class="flex flex-col items-center bg-cidst-surface border border-cidst-border rounded p-3 hover:shadow-lg transition-shadow">
                        @if ($pdf->thumbnail_path)
                            <img src="{{ Storage::url($pdf->thumbnail_path) }}"
                                 class="w-full h-32 object-cover rounded border border-cidst-border mb-2"
                                 alt="{{ $pdf->original_name ?? basename($pdf->path) }}">
                        @else
                            <div class="w-full h-32 flex items-center justify-center bg-cidst-red/5 rounded border border-cidst-border mb-2 text-3xl">
                                📄
                            </div>
                        @endif
                        <span class="text-cidst-ink text-xs text-center line-clamp-2">
                            {{ $pdf->original_name ?? basename($pdf->path) }}
                        </span>
                        <span class="text-cidst-red text-xs font-medium mt-1">
                            Télécharger &rarr;
                        </span>
                    </a>
                @endforeach
            </div>
        @endif

        <a href="{{ route('documents.index') }}" class="inline-block mt-6 text-cidst-red text-sm font-medium">
            &larr; Retour aux documents
        </a>
    </div>
</x-layout>