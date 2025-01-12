@props([
    'size' => null,
])

<x-button
    {{ $attributes->merge()->class([
            'text-white bg-primary hover:bg-primary-active hover:shadow-primary active:bg-primary-active active:shadow-primary focus:bg-primary-active focus:shadow-primary',
        ]) }}>
    {{ $slot }}
</x-button>
