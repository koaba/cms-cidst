<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-primary text-white']) }}>
    {{ $slot }}
</button>
