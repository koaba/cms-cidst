@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-base-300 focus:border-primary focus:ring-primary rounded-md shadow-sm']) }}>
