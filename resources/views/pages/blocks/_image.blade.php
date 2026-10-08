@if($media->isNotEmpty())
    <figure class="my-4">
        <x-media-img :media="$media->first()" :alt="$data['alt'] ?? ''" sizes="(min-width: 1024px) 768px, 100vw" class="w-full rounded" />
        @if(!empty($data['caption']))
            <figcaption class="text-sm text-gray-500 mt-1">{{ $data['caption'] }}</figcaption>
        @endif
    </figure>
@endif