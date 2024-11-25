@props([
    'size' => ''
])

<x-button
    {{ $attributes->merge(['class' => 'text-white bg-primary border-gray-300 hover:bg-primary-active hover:shadow-primary active:bg-primary-active active:shadow-primary focus:bg-primary-active focus:shadow-primary']) }}
>
    {{ $slot }}
</x-button>
