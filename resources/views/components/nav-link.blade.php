@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center border-b-2 border-cyan-600 px-1 pt-1 text-sm font-semibold leading-5 text-cyan-700 transition'
            : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium leading-5 text-slate-500 transition hover:text-slate-700 hover:border-slate-300 focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
