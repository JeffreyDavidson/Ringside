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
            'text-white bg-info hover:bg-info-active hover:shadow-info-box-shadow active:bg-info-active active:shadow-info-box-shadow focus:bg-info-active focus:shadow-info-box-shadow',
        ]) }}
    :$size :$outline :$withIcon :$iconOnly :$clear :$disabled>
    {{ $slot }}
</x-button>
