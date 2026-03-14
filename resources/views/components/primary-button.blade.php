<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-pill btn-pill-dark inline-flex items-center justify-center']) }}>
    {{ $slot }}
</button>
