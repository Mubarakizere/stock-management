@props(['active' => false, 'href'])

@php
$classes = $active
    ? 'flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-semibold bg-teal-500/10 text-teal-400 transition-colors shadow-xs'
    : 'flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-medium text-gray-400 hover:text-gray-200 hover:bg-gray-800/60 transition-colors';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
