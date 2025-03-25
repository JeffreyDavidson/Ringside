@props([
    'size' => 'default',
    'variant' => 'default',
    'withIcon' => false,
    'iconOnly' => false,
])

<x-button
    {{ $attributes->merge()->class([
        'bg-warning-light' => $variant === 'outline',
        'bg-warning' => $variant === 'default',
        'border border-solid border-warning-clarity' => $variant !== 'clear',
        'text-white hover:bg-warning-active hover:shadow hover:shadow-warning active:bg-warning-active active:shadow active:shadow-warning focus:bg-warning-active focus:shadow focus:shadow-warning' =>
            $variant === 'default',
        'text-warning hover:bg-warning hover:shadow-none hover:text-warning-inverse hover:border-warning active:text-warning-inverse active:bg-warning active:border-warning active:shadow-none focus:text-warning-inverse focus:bg-warning focus:border-warning focus:shadow-none' =>
            $variant !== 'default',
    ]) }}
    :$size>
    {{ $slot }}
</x-button>
