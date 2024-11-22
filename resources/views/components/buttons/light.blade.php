@props([
    'size' => ''
])

<x-button
    {{ $attributes->merge(['class' => 'text-gray-700 bg-white border-gray-300 hover:bg-[#fcfcfc] hover:text-gray-700 hover:border-gray-300 hover:shadow-[0_4px_12px_0px_rgba(0,0,0,0.09)] active:bg-[#fcfcfc] active:text-gray-700 active:border-gray-300 active:shadow-[0_4px_12px_0px_rgba(0,0,0,0.09)] focus:bg-[#fcfcfc] focus:text-gray-700 focus:border-gray-300 focus:shadow-[0_4px_12px_0px_rgba(0,0,0,0.09)]']) }}
>
    {{ $slot }}
</x-button>
