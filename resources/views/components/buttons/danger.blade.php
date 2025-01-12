@props([
    'size' => null,
])

<x-button
    {{ $attributes->merge()->class([
            'text-white bg-danger hover:bg-danger-active hover:shadow-danger-box-shadow active:bg-danger-active active:shadow-danger-box-shadow focus:bg-danger-active focus:shadow-danger-box-shadow',
        ]) }}>
    {{ $slot }}
</x-button>
