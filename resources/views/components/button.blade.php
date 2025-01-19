@props([
    'size' => null,
    'outline' => false,
    'withIcon' => false,
    'iconOnly' => false,
    'clear' => false,
    'disabled' => false,
])

<button
    {{ $attributes->class([
        'inline-flex items-center cursor-pointer leading-none rounded-md font-medium border border-solid border-transparent',
        'h-7 ps-2 pe-2 text-2xs gap-1' => $size === 'xs',
        'h-8 ps-3 pe-3 text-xs gap-1.25' => $size === 'sm',
        'h-10 ps-4 pe-4 text-2sm gap-1.5' => $size === null || $size === '',
        'h-12 ps-5 pe-5 text-sm gap-2' => $size === 'lg',
        'justify-center shrink-0 p-0 gap-0 w-10' => $iconOnly,
        'w-7' => $size === 'xs' && $iconOnly,
    ]) }}>
    {{ $slot }}
</button>
