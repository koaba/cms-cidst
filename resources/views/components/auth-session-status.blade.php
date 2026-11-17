@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-cidst-success']) }}>
        {{ $status }}
    </div>
@endif
