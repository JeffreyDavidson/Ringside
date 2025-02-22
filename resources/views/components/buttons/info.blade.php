@props([
    'size' => 'default',
    'variant' => 'default',
])

<x-button
    {{ $attributes->merge()->class([
        'bg-info-light' => $variant === 'outline',
        'bg-info' => $variant === 'default',
        'border border-solid border-info-clarity' => $variant !== 'clear',
        'text-white hover:bg-info-active hover:shadow hover:shadow-info active:bg-info-active active:shadow active:shadow-info focus:bg-info-active focus:shadow focus:shadow-info' =>
            $variant === 'default',
        'text-info hover:bg-info hover:shadow-none hover:text-info-inverse hover:border-info active:text-info-inverse active:bg-info active:border-info active:shadow-none focus:text-info-inverse focus:bg-info focus:border-info focus:shadow-none' =>
            $variant !== 'default',
    ]) }}
    :$size>
    {{ $slot }}
</x-button>
