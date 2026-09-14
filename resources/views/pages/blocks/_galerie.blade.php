@if(($data['layout'] ?? 'grid') === 'carousel')
    <div class="flex gap-4 overflow-x-auto snap-x snap-mandatory pb-2 my-6">
        @foreach($media as $item)
            <figure class="snap-start shrink-0 w-72">
                <img src="{{ $item->display_url }}" alt="{{ $item->pivot->alt ?? '' }}" loading="lazy" class="w-full h-48 object-cover rounded-lg">
                @if($item->pivot->caption)
                    <figcaption class="text-sm text-gray-500 mt-1">{{ $item->pivot->caption }}</figcaption>
                @endif
            </figure>
        @endforeach
    </div>
@else
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 my-6">
        @foreach($media as $item)
            <figure>
                <img src="{{ $item->display_url }}" alt="{{ $item->pivot->alt ?? '' }}" loading="lazy" class="w-full h-40 object-cover rounded-lg">
                @if($item->pivot->caption)
                    <figcaption class="text-sm text-gray-500 mt-1">{{ $item->pivot->caption }}</figcaption>
                @endif
            </figure>
        @endforeach
    </div>
@endif