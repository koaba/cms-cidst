<x-layout :seo="$page">
    <h1 class="text-3xl font-bold mb-4">{{ $page->title }}</h1>
    @if($page->media->isNotEmpty())
        <x-media-img :media="$page->media->first()" :alt="$page->title" :priority="true" sizes="(min-width: 672px) 672px, 100vw" class="w-full max-w-2xl mb-4 rounded" />
    @endif

     @if($page->blocks->whereNull('parent_id')->isNotEmpty())
        <div class="page-blocks space-y-6">
            @foreach($page->blocks->whereNull('parent_id') as $block)
               @include('pages.blocks._' . $block->type, ['data' => $block->data, 'media' => $block->media, 'block' => $block])
            @endforeach
        </div>
    @else
        <div class="prose max-w-none">
            {!! nl2br(e($page->content)) !!}
        </div>
    @endif
    <a href="{{ route('pages.index') }}" class="text-blue-600 mt-4 inline-block">&larr; Retour aux pages</a>
</x-layout>