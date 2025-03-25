@aware([
    'size' => 'default',
])

@props([
    'isActive' => false,
])

<x-button
    {{ $attributes->merge()->class([
            'justify-center shrink-0 p-0 gap-0 hover:bg-light hover:border hover:border-solid hover:border-gray-200 hover:text-gray-900 hover:shadow-light',
            'w-[1.624rem] h-[1.624rem] text-sm' => $size === 'sm',
            'w-8 h-8 text-lg' => $size === 'default',
            'w-[2.374rem] h-[2.374rem] text-xl' => $size === 'lg',
            'bg-light border border-solid border-gray-200 text-gray-900 shadow-light' => $isActive,
        ]) }}
    :$size>
    {{ $slot }}
</x-button>
