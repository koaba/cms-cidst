@if($items->count() > 0)
<div class="bg-red-600 text-white overflow-hidden whitespace-nowrap py-2">
    <div class="inline-block animate-marquee">
        @foreach($items as $item)
            @if($item->link_url)
                <a href="{{ $item->link_url }}" class="mx-8 hover:underline">{{ $item->content }}</a>
            @else
                <span class="mx-8">{{ $item->content }}</span>
            @endif
        @endforeach
    </div>
</div>
@endif