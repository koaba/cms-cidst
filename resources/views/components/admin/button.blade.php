@props(['href' => null, 'type' => 'submit'])
@if($href)
    <a href="{{ $href }}"
       {{ $attributes->merge(['class' => 'btn btn-primary text-white']) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}"
            {{ $attributes->merge(['class' => 'btn btn-primary text-white']) }}>
        {{ $slot }}
    </button>
@endif