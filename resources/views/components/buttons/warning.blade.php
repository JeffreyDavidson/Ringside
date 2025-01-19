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
            'text-white bg-warning hover:bg-warning-active hover:shadow-warning active:bg-warning-active active:shadow-warning-box-shadow focusr:bg-warning-active focus:shadow-warning-box-shadow',
        ]) }}
    :$size :$outline :$withIcon :$iconOnly :$clear :$disabled>
    {{ $slot }}
</x-button>
