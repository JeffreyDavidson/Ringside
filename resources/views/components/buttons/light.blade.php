@props([
    'size' => 'default',
    'variant' => 'default',
    'disabled' => false,
])

<x-button
    {{ $attributes->merge()->class([
        'hover:text-gray-800 hover:bg-gray-200 hover:border-transparent hover:shadow-none active:text-gray-800 active:bg-gray-200 active:border-transparent active:shadow-none focus:text-gray-800 focus:bg-gray-200 focus:border-transparent focus:shadow-none' =>
            $variant === 'clear',
        'border border-solid bg-light hover:bg-light-active hover:text-gray-800 hover:border-gray-300 hover:shadow hover:shadow-default active:bg-light-active active:text-gray-800 active:border-gray-300 active:shadow active:shadow-default focus:bg-light-active focus:text-gray-800 focus:border-gray-300 focus:shadow focus:shadow-default' =>
            $variant === 'default',
        'opacity-100 pointer-events-none bg-light' => $disabled,
        'text-gray-700' => !$disabled,
        'text-gray-500' => $disabled,
        'border-gray-200' => $variant === 'default' && $disabled,
        'border-gray-300' => $variant === 'default' && !$disabled,
    ]) }}
    :$size>
    {{ $slot }}
</x-button>
