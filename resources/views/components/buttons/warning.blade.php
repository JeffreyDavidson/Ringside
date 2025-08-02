@props([
    'size' => 'default',
    'variant' => 'default',
    'disabled' => false,
    'withIcon' => false,
    'iconOnly' => false,
])

<x-button
    {{ $attributes->merge()->class([
        'btn-warning-default btn-warning-states' => $variant === 'default',
        'btn-warning-outline btn-warning-outline-states' => $variant === 'outline',
        'opacity-50 pointer-events-none' => $disabled,
    ]) }}
    :$size>
    {{ $slot }}
</x-button>
