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
            'text-white bg-primary hover:bg-primary-active hover:shadow-primary active:bg-primary-active active:shadow-primary focus:bg-primary-active focus:shadow-primary',
        ]) }}
    :$size :$outline :$withIcon :$iconOnly :$clear :$disabled>
    {{ $slot }}
</x-button>
