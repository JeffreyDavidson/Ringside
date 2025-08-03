@props(['columns' => 2])

@php
    $classes = match($columns) {
        2 => 'grid grid-cols-1 md:grid-cols-2 gap-4',
        3 => 'grid grid-cols-1 md:grid-cols-3 gap-4',
        4 => 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4',
        default => 'grid grid-cols-1 md:grid-cols-2 gap-4'
    };
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>