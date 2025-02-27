@props([
    'size' => 'default',
    'variant' => 'default',
    'withIcon' => false,
    'iconOnly' => false,
])

<x-button
    {{ $attributes->merge()->class([
        'bg-success-light' => $variant === 'outline',
        'bg-success' => $variant === 'default',
        'border border-solid border-success-clarity' => $variant !== 'clear',
        'text-white hover:bg-success-active hover:shadow hover:shadow-success active:bg-success-active active:shadow active:shadow-success focus:bg-success-active focus:shadow focus:shadow-success' =>
            $variant === 'default',
        'text-success hover:bg-success hover:shadow-none hover:text-success-inverse hover:border-success active:text-success-inverse active:bg-success active:border-success active:shadow-none focus:text-success-inverse focus:bg-success focus:border-success focus:shadow-none' =>
            $variant !== 'default',
    ]) }}
    :$size :$withIcon :$iconOnly>
    {{ $slot }}
</x-button>
