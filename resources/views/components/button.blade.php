@props([
    'variant' => 'primary',
    'size' => 'md',
    'iconOnly' => false,
    'tag' => 'button',
])

@php
    $sizes = [
        'xs' => 'h-7 px-2 text-2xs gap-1',
        'sm' => 'h-8 px-3 text-xs gap-1.5',
        'md' => 'h-9 px-4 text-sm gap-1.5',
        'lg' => 'h-10 px-5 text-sm gap-2',
    ];

    $iconOnlySizes = [
        'xs' => 'size-7',
        'sm' => 'size-8',
        'md' => 'size-9',
        'lg' => 'size-10',
    ];

    $variants = [
        'primary' => 'btn-primary-default btn-primary-states',
        'success' => 'btn-success-default btn-success-states',
        'danger' => 'btn-danger-default btn-danger-states',
        'warning' => 'btn-warning-default btn-warning-states',
        'info' => 'btn-info-default btn-info-states',
        'light' => 'btn-light-default btn-light-states',
        'secondary' => 'btn-secondary-default btn-secondary-states',
        'link' => 'text-gray-700 hover:text-primary bg-transparent',
    ];

    $base = 'inline-flex items-center justify-center cursor-pointer font-medium rounded-md transition-all';
    $sizeClass = $iconOnly
        ? $iconOnlySizes[$size] ?? $iconOnlySizes['md']
        : $sizes[$size] ?? $sizes['md'];
    $variantClass = $variants[$variant] ?? $variants['primary'];
@endphp

<{{ $tag }} {{ $attributes->merge(['type' => $tag === 'button' ? 'button' : null])->class([
    $base,
    $sizeClass,
    $variantClass,
]) }}>
    {{ $slot }}
</{{ $tag }}>
