@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between mt-8">
        <div class="flex-1 flex justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="px-4 py-2 text-sm text-cidst-muted border border-cidst-border rounded cursor-not-allowed">&laquo; Précédent</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="px-4 py-2 text-sm text-cidst-ink border border-cidst-border rounded hover:bg-cidst-bg">&laquo; Précédent</a>
            @endif
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="px-4 py-2 text-sm text-cidst-ink border border-cidst-border rounded hover:bg-cidst-bg">Suivant &raquo;</a>
            @else
                <span class="px-4 py-2 text-sm text-cidst-muted border border-cidst-border rounded cursor-not-allowed">Suivant &raquo;</span>
            @endif
        </div>
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-cidst-muted">
                    Affichage de <span class="font-medium text-cidst-ink">{{ $paginator->firstItem() }}</span>
                    à <span class="font-medium text-cidst-ink">{{ $paginator->lastItem() }}</span>
                    sur <span class="font-medium text-cidst-ink">{{ $paginator->total() }}</span> résultats
                </p>
            </div>
            <div>
                <span class="inline-flex gap-1">
                    @if ($paginator->onFirstPage())
                        <span class="px-3 py-1.5 text-sm text-cidst-muted border border-cidst-border rounded cursor-not-allowed">&laquo;</span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-cidst-ink border border-cidst-border rounded hover:bg-cidst-bg">&laquo;</a>
                    @endif

                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span class="px-3 py-1.5 text-sm text-cidst-muted">{{ $element }}</span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span class="px-3 py-1.5 text-sm font-medium bg-cidst-red text-white rounded">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="px-3 py-1.5 text-sm text-cidst-ink border border-cidst-border rounded hover:bg-cidst-bg">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-cidst-ink border border-cidst-border rounded hover:bg-cidst-bg">&raquo;</a>
                    @else
                        <span class="px-3 py-1.5 text-sm text-cidst-muted border border-cidst-border rounded cursor-not-allowed">&raquo;</span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif