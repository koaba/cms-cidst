@php
    $embedUrl = \App\Support\VideoEmbed::embedUrl($data['url'] ?? null);
@endphp

<div class="my-4">
    @if(!empty($data['title']))
        <h3 class="text-lg font-semibold mb-2">{{ $data['title'] }}</h3>
    @endif

    @if(($data['source_type'] ?? 'upload') === 'upload' && $media->isNotEmpty())
        <video controls class="w-full rounded">
            <source src="{{ Storage::url($media->first()->path) }}" type="{{ $media->first()->mime_type }}">
        </video>
    @elseif(!empty($embedUrl))
        <div class="aspect-video">
            <iframe src="{{ $embedUrl }}" class="w-full h-full rounded" allowfullscreen></iframe>
        </div>
    @endif
</div>