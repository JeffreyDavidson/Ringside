@props([
    'size' => null,
])

<x-button
    {{ $attributes->merge()->class([
            'text-white bg-info hover:bg-info-active hover:shadow-info-box-shadow active:bg-info-active active:shadow-info-box-shadow focus:bg-info-active focus:shadow-info-box-shadow',
        ]) }}>
    {{ $slot }}
</x-button>
