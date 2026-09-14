<x-layout :seo="$page">
    <h1 class="text-3xl font-bold mb-4">{{ $page->title }}</h1>
    @if($page->media->isNotEmpty())
        <img src="{{ Storage::url($page->media->first()->path) }}" class="w-full max-w-2xl mb-4 rounded">
    @endif

   @if($page->blocks->isNotEmpty())
        <div class="page-blocks space-y-6">
            @foreach($page->blocks as $block)
               @include('pages.blocks._' . $block->type, ['data' => $block->data, 'media' => $block->media])
            @endforeach
        </div>
    @else
        <div class="prose max-w-none">
            {!! nl2br(e($page->content)) !!}
        </div>
    @endif
    <a href="{{ route('pages.index') }}" class="text-blue-600 mt-4 inline-block">&larr; Retour aux pages</a>
</x-layout>