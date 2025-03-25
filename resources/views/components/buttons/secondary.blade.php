@props([
    'size' => 'default',
    'variant' => 'default',
    'withIcon' => false,
    'iconOnly' => false,
    'disabled' => false,
])

<x-button
    {{ $attributes->merge()->class([
        'border border-solid border-gray-200 hover:bg-light-active hover:text-gray-800 hover:border-gray-300 hover:shadow hover:shadow-default active:bg-light-active active:text-gray-800 active:border-gray-300 active:shadow active:shadow-default focus:bg-light-active focus:text-gray-800 focus:border-gray-300 focus:shadow focus:shadow-default' =>
            $variant === 'default',
        'opacity-100 pointer-events-none' => $disabled,
        'text-gray-700 bg-secondary' => !$disabled,
        'text-gray-500 bg-light' => $disabled,
    ]) }}
    :$size>
    {{ $slot }}
</x-button>
