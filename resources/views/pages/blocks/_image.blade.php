@if($media->isNotEmpty())
    <figure class="my-4">
        <img src="{{ Storage::url($media->first()->path) }}" alt="{{ $data['alt'] ?? '' }}" loading="lazy" decoding="async" class="w-full rounded">
        @if(!empty($data['caption']))
            <figcaption class="text-sm text-gray-500 mt-1">{{ $data['caption'] }}</figcaption>
        @endif
    </figure>
@endif