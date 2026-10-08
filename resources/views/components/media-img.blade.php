@props(['media', 'alt' => '', 'sizes' => '100vw', 'priority' => false, 'class' => ''])

@php
    $disk = Storage::disk('public');
    $original = $media->display_url;
    $candidates = collect($media->variants ?? [])
        ->sortKeys()
        ->map(fn ($path, $width) => $disk->url($path).' '.$width.'w');

    if ($candidates->isNotEmpty() && $media->width) {
        $candidates->push($original.' '.$media->width.'w');
    }

    $srcset = $candidates->implode(', ');
    $hasDimensions = $media->width && $media->height;
    $extra = $attributes->toHtml();
@endphp
<img src="{{ $original }}" alt="{{ $alt }}" @if($srcset !== '') srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif @if($hasDimensions) width="{{ $media->width }}" height="{{ $media->height }}" @endif @if($priority) fetchpriority="high" @else loading="lazy" @endif decoding="async" class="{{ $class }}" {!! $extra !!}>