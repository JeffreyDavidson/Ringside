@props([
    'size' => null,
    'outline' => false,
    'withIcon' => false,
    'iconOnly' => false,
    'clear' => false,
    'disabled' => false,
])

<x-button
    {{ $attributes->merge()->class([
            'text-gray-700 bg-light border solid border-gray-300 hover:bg-light-active hover:text-gray-800 hover:border-gray-300 hover:shadow-warning active:bg-light-active active:text-gray-800 active:border-gray-300 active:shadow-default focus:bg-light-active focus:text-gray-800 focus:border-gray-300 focus:shadow-default',
        ]) }}
    :$size :$outline :$withIcon :$iconOnly :$clear :$disabled>
    {{ $slot }}
</x-button>
