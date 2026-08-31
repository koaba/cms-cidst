@php
    $position = config('watermark.position', 'br');
    $opacity = config('watermark.opacity', 25) / 100;
    $sizePercent = config('watermark.size_percent', 7);
    $marginPercent = config('watermark.margin_percent', 2);

    $posClasses = match ($position) {
        'br' => 'bottom-0 right-0',
        'bl' => 'bottom-0 left-0',
        'tr' => 'top-0 right-0',
        'tl' => 'top-0 left-0',
        default => 'bottom-0 right-0',
    };
@endphp

<img
    src="{{ Storage::url(config('watermark.logo_path')) }}"
    alt=""
    aria-hidden="true"
    class="absolute {{ $posClasses }} pointer-events-none select-none z-10"
    style="width: {{ $sizePercent }}%; margin: {{ $marginPercent }}%; opacity: {{ $opacity }};"
>