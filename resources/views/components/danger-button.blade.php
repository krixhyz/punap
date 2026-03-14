<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-full bg-rose-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-rose-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-300']) }}>
    {{ $slot }}
</button>
