@props([
    'size' => 'default',
    'variant' => 'default',
    'disabled' => false,
])

<x-button
    {{ $attributes->merge()->class([
        'bg-primary-light' => $variant === 'outline',
        'bg-primary' => $variant === 'default',
        'opacity-50 pointer-events-none' => $disabled,
        'border border-solid border-primary-clarity' => $variant !== 'clear',
        'text-white hover:bg-primary-active hover:shadow hover:shadow-primary active:bg-primary-active active:shadow active:shadow-primary focus:bg-primary-active focus:shadow focus:shadow-primary' =>
            $variant === 'default',
        'text-primary hover:bg-primary hover:shadow-none hover:text-primary-inverse hover:border-primary active:text-primary-inverse active:bg-primary active:border-primary active:shadow-none focus:text-primary-inverse focus:bg-primary focus:border-primary focus:shadow-none' =>
            $variant !== 'default',
    ]) }}
    :$size>
    {{ $slot }}
</x-button>
