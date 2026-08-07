@if($items->count() > 0)
    @if($direction === 'vertical')
        <div class="bg-cidst-red text-white overflow-hidden h-10 relative">
            <div class="animate-marquee-vertical absolute w-full">
                @foreach($items as $item)
                    <div class="py-2 text-center">
                        @if($item->link_url)
                            <a href="{{ $item->link_url }}" class="hover:underline">{{ $item->content }}</a>
                        @else
                            <span>{{ $item->content }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="bg-cidst-red text-white overflow-hidden whitespace-nowrap py-2">
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
@endif