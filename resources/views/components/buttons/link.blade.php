@props([
    'size' => 'default',
    'variant' => 'default',
])

<x-button
    {{ $attributes->merge()->class([
        'text-primary font-2sm h-auto pb-1 ps-0 pe-0 rounded-none bg-transparent border-b border-dashed border-primary' =>
            $variant === 'default',
    ]) }}
    :$size>
    {{ $slot }}
</x-button>
