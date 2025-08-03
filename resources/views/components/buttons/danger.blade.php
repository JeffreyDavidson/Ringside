@props([
    'size' => 'default',
    'variant' => 'default',
    'disabled' => false,
    'withIcon' => false,
    'iconOnly' => false,
])

<x-button
    {{ $attributes->merge()->class([
        'btn-danger-default btn-danger-states' => $variant === 'default',
        'btn-danger-outline btn-danger-outline-states' => $variant === 'outline',
        'opacity-50 pointer-events-none' => $disabled,
    ]) }}
    :$size>
    {{ $slot }}
</x-button>
