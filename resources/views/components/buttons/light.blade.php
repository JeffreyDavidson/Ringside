@props([
    'size' => 'default',
    'variant' => 'default',
    'disabled' => false,
    'withIcon' => false,
    'iconOnly' => false,
])

<x-button
    {{ $attributes->merge()->class([
        'btn-light-default btn-light-states' => $variant === 'default',
        'hover:text-gray-800 hover:bg-gray-200 hover:border-transparent hover:shadow-none active:text-gray-800 active:bg-gray-200 active:border-transparent active:shadow-none focus:text-gray-800 focus:bg-gray-200 focus:border-transparent focus:shadow-none' => $variant === 'clear',
        'opacity-100 pointer-events-none bg-light' => $disabled,
        'text-gray-700' => !$disabled,
        'text-gray-500' => $disabled,
        'border-gray-200' => $variant === 'default' && $disabled,
        'border-gray-300' => $variant === 'default' && !$disabled,
    ]) }}
    :$size>
    {{ $slot }}
</x-button>
