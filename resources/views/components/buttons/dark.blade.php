@props([
    'size' => 'default',
    'variant' => 'default',
    'disabled' => false,
    'withIcon' => false,
    'iconOnly' => false,
])

<x-button
    {{ $attributes->merge()->class([
        'btn-dark-default btn-dark-states' => $variant === 'default',
        'btn-dark-outline btn-dark-outline-states' => $variant === 'outline',
        'opacity-50 pointer-events-none' => $disabled,
    ]) }}
    :$size>
    {{ $slot }}
</x-button>
