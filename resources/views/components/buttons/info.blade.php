@props([
    'size' => 'default',
    'variant' => 'default',
    'disabled' => false,
    'withIcon' => false,
    'iconOnly' => false,
])

<x-button
    {{ $attributes->merge()->class([
        'btn-info-default btn-info-states' => $variant === 'default',
        'btn-info-outline btn-info-outline-states' => $variant === 'outline',
        'opacity-50 pointer-events-none' => $disabled,
    ]) }}
    :$size>
    {{ $slot }}
</x-button>
