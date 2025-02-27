@props([
    'size' => 'default',
    'isActive' => false,
])

<button
    {{ $attributes->class([
        'inline-flex items-center cursor-pointer leading-none rounded-md',
        'h-7 ps-2 pe-2 font-medium text-2xs gap-1' => $size === 'xs',
        'h-8 ps-3 pe-3 font-medium text-xs gap-1.25' => $size === 'sm',
        'h-10 ps-4 pe-4 font-medium text-2sm gap-1.5' => $size === 'default',
        'h-12 ps-5 pe-5 font-medium text-sm gap-2' => $size === 'lg',
        'bg-light border border-solid border-gray-200 text-gray-900 shadow-light' => $isActive,
    ]) }}>
    {{ $slot }}
</button>
