@props([
    'size' => 'default',
    'variant' => 'default',
])

<x-button
    {{ $attributes->merge()->class([
        'text-white bg-dark hover:bg-dark-active hover:shadow hover:shadow-dark active:bg-dark-active active:shadow active:shadow-dark focus:bg-dark-active focus:shadow focus:shadow-dark' =>
            $variant === 'default',
    ]) }}
    :$size>
    {{ $slot }}
</x-button>
