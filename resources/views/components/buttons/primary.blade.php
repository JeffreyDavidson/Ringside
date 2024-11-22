@props([
    'size' => ''
])

<x-button
    {{ $attributes->merge(['class' => 'text-white bg-[#1b84ff] border-gray-300 hover:bg-[#056ee9] hover:shadow-[0_4px_12px_0px_rgba(0,0,0,0.35)] active:bg-[#056ee9] active:shadow-[0_4px_12px_0px_rgba(0,0,0,0.35)]']) }}
>
    {{ $slot }}
</x-button>
