@php
    $settings = \App\Models\SiteSetting::current();

    $columnsClass = match ((int) $settings->pages_grid_columns) {
        3 => 'sm:grid-cols-3',
        4 => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid-cols-2',
    };

    $imageAspectClass = match ($settings->pages_image_size) {
        'small' => 'aspect-[4/3]',
        'large' => 'aspect-[21/9]',
        default => 'aspect-video',
    };
@endphp

<x-layout>
    <h1 class="text-3xl font-display font-bold mb-6 text-cidst-ink">Pages</h1>

    @if ($pages->isEmpty())
        <p class="text-cidst-muted">Aucune page pour le moment.</p>
    @else
    <div class="grid gap-6 {{ $columnsClass }}">
        @foreach ($pages as $page)
            <a href="{{ route('pages.show', $page) }}"
               class="flex flex-col h-full bg-cidst-surface border border-cidst-border rounded overflow-hidden hover:shadow-lg transition-shadow">
                @if ($page->media->isNotEmpty())
                    <img src="{{ Storage::url($page->media->first()->path) }}"
                         alt="{{ $page->title }}"
                         class="w-full {{ $imageAspectClass }} object-cover">
                @else
                    <div class="w-full {{ $imageAspectClass }} bg-cidst-bg flex items-center justify-center text-cidst-muted text-sm">
                        Aucune image
                    </div>
                @endif
                <div class="p-4 flex flex-col flex-1">
                    <h2 class="text-lg font-display font-semibold text-cidst-ink line-clamp-2">
                        {{ $page->title }}
                    </h2>
                    <p class="text-cidst-ink text-sm mt-2 line-clamp-3">
                        {{ Str::limit(strip_tags($page->content), 120) }}
                    </p>
                    <span class="text-cidst-red text-sm font-medium mt-2">Lire la suite &rarr;</span>
                </div>
            </a>
        @endforeach
    </div>
    {{ $pages->links() }}
    @endif
</x-layout>