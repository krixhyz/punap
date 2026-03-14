@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full border-l-4 border-cyan-600 bg-cyan-50 py-2 ps-3 pe-4 text-start text-base font-semibold text-cyan-700 transition focus:outline-none'
            : 'block w-full border-l-4 border-transparent py-2 ps-3 pe-4 text-start text-base font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800 focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
