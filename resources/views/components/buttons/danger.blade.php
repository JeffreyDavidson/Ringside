@props([
    'size' => 'default',
    'variant' => 'default',
])

<x-button
    {{ $attributes->merge()->class([
        'bg-danger-light' => $variant === 'outline',
        'bg-danger' => $variant === 'default',
        'border border-solid border-danger-clarity' => $variant !== 'clear',
        'text-white hover:bg-danger-active hover:shadow hover:shadow-danger active:bg-danger-active active:shadow active:shadow-danger focus:bg-danger-active focus:shadow focus:shadow-danger' =>
            $variant === 'default',
        'text-danger hover:bg-danger hover:shadow-none hover:text-danger-inverse hover:border-danger active:text-danger-inverse active:bg-danger active:border-danger active:shadow-none focus:text-danger-inverse focus:bg-danger focus:border-danger focus:shadow-none' =>
            $variant !== 'default',
    ]) }}
    :$size>
    {{ $slot }}
</x-button>
