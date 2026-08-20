<x-layout>
    @if (!isset($pdfCategory) && ($settings->pdf_documents_title || $settings->pdf_documents_content))
        <div class="mb-6">
            @if ($settings->pdf_documents_title)
                <h1 class="text-3xl font-display font-bold mb-2 text-cidst-ink">
                    {{ $settings->pdf_documents_title }}
                </h1>
            @endif
            @if ($settings->pdf_documents_content)
                <p class="text-cidst-muted">
                    {!! nl2br(e($settings->pdf_documents_content)) !!}
                </p>
            @endif
        </div>
    @endif

    <h1 class="text-3xl font-display font-bold mb-6 text-cidst-ink">
        @isset($pdfCategory)
            Documents dans : {{ $pdfCategory->name }}
        @else
            Documents PDF
        @endisset
    </h1>
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('documents.index') }}"
           class="text-xs px-2 py-1 rounded-full border {{ !isset($pdfCategory) ? 'bg-cidst-red text-white border-cidst-red' : 'border-cidst-border text-cidst-ink' }}">
            Toutes les catégories
        </a>
        @foreach (\App\Models\PdfCategory::orderBy('name')->get() as $cat)
            <a href="{{ route('documents.category', $cat) }}"
               class="text-xs px-2 py-1 rounded-full border {{ isset($pdfCategory) && $pdfCategory->id === $cat->id ? 'bg-cidst-red text-white border-cidst-red' : 'border-cidst-border text-cidst-ink' }}">
                {{ $cat->name }}
            </a>
        @endforeach
    </div>
    @if ($documents->isEmpty())
        <p class="text-cidst-muted">Aucun document pour le moment.</p>
    @else
        <div class="grid gap-6 sm:grid-cols-2">
            @foreach ($documents as $document)
                @php $firstPdf = $document->pdfs->first(); @endphp
                <a href="{{ route('documents.show', $document) }}"
                   class="flex flex-col h-full bg-cidst-surface border border-cidst-border rounded overflow-hidden hover:shadow-lg transition-shadow p-4">
                    @if ($firstPdf && $firstPdf->thumbnail_path)
                        <img src="{{ Storage::url($firstPdf->thumbnail_path) }}"
                             class="w-full h-32 object-cover rounded border border-cidst-border mb-3"
                             alt="{{ $document->title }}">
                    @else
                        <div class="w-full h-32 flex items-center justify-center bg-cidst-red/5 rounded border border-cidst-border mb-3 text-3xl">
                            📄
                        </div>
                    @endif
                    <span class="text-[10px] leading-none bg-cidst-red/10 text-cidst-red px-1.5 py-0.5 rounded-full self-start mb-2">
                        {{ $document->category->name }}
                    </span>
                    <h2 class="text-lg font-display font-semibold text-cidst-ink line-clamp-2">
                        {{ $document->title }}
                    </h2>
                    @if ($document->description)
                        <p class="text-cidst-ink text-sm mt-2 line-clamp-3">
                            {{ Str::limit($document->description, 120) }}
                        </p>
                    @endif
                    <span class="text-cidst-muted text-xs mt-2">{{ $document->pdfs->count() }} fichier(s) PDF</span>
                    <span class="text-cidst-red text-sm font-medium mt-2">Voir les documents &rarr;</span>
                </a>
            @endforeach
        </div>
        {{ $documents->links() }}
    @endif
</x-layout>