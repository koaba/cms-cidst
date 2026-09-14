@php
    $embedUrl = null;
    if (!empty($data['url'])) {
        $rawUrl = $data['url'];

        // youtu.be/XXXX
        if (preg_match('#youtu\.be/([A-Za-z0-9_-]+)#', $rawUrl, $m)) {
            $embedUrl = 'https://www.youtube.com/embed/' . $m[1];
        }
        // youtube.com/shorts/XXXX
        elseif (preg_match('#youtube\.com/shorts/([A-Za-z0-9_-]+)#', $rawUrl, $m)) {
            $embedUrl = 'https://www.youtube.com/embed/' . $m[1];
        }
        // youtube.com/watch?v=XXXX
        elseif (preg_match('#youtube\.com/watch\?v=([A-Za-z0-9_-]+)#', $rawUrl, $m)) {
            $embedUrl = 'https://www.youtube.com/embed/' . $m[1];
        }
        // déjà au format embed, ou vimeo, ou autre : on laisse tel quel
        else {
            $embedUrl = $rawUrl;
        }
    }
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