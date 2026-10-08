@if($media->isNotEmpty())
    <figure class="my-4">
        @php
            $image = $media->first();
            $width = $image->width;
            $height = $image->height;
        @endphp
        <img src="{{ Storage::url($image->path) }}" alt="{{ $data['alt'] ?? '' }}" @if($width && $height) width="{{ $width }}" height="{{ $height }}" @endif loading="lazy" decoding="async" class="w-full rounded">
        @if(!empty($data['caption']))
            <figcaption class="text-sm text-gray-500 mt-1">{{ $data['caption'] }}</figcaption>
        @endif
    </figure>
@endif