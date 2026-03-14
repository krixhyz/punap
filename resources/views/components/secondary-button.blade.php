<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn-pill btn-pill-soft inline-flex items-center justify-center']) }}>
    {{ $slot }}
</button>
