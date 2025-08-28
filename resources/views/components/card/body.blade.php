@props([
    'variant' => 'default',
    'class' => '',
])

@php
$classes = collect([
    'card-body',
    
    // Padding variants
    match($variant) {
        'compact' => 'p-4',
        'spacious' => 'p-8',
        default => 'p-6'
    }
])->implode(' ');
@endphp

<div {{ $attributes->merge(['class' => $classes . ' ' . $class]) }}>
    {{ $slot }}
</div>