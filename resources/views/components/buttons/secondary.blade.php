@props([
    'size' => 'default',
    'variant' => 'default',
    'withIcon' => false,
    'iconOnly' => false,
    'disabled' => false,
])

<x-button
    {{ $attributes->merge()->class([
        'btn-secondary-default btn-secondary-states' => $variant === 'default' && !$disabled,
        'opacity-100 pointer-events-none' => $disabled,
        'text-gray-500 bg-light border border-solid border-gray-200' => $disabled,
    ]) }}
    :$size>
    {{ $slot }}
</x-button>
