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
            <ul class="space-y-2">
                @foreach ($document->pdfs as $pdf)
                    <li class="flex items-center justify-between bg-cidst-surface border border-cidst-border rounded p-3">
                        <span class="text-cidst-ink text-sm">
                            📄 {{ $pdf->original_name ?? basename($pdf->path) }}
                        </span>
                        <a href="{{ Storage::url($pdf->path) }}" target="_blank"
                           class="text-cidst-red text-sm font-medium">
                            Télécharger &rarr;
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        <a href="{{ route('documents.index') }}" class="inline-block mt-6 text-cidst-red text-sm font-medium">
            &larr; Retour aux documents
        </a>
    </div>
</x-layout>