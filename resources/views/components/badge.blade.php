@props([
    'size' => 'sm',
    'color' => 'gray',
])

@php
    $sizes = [
        'xs' => 'h-[1rem] min-w-[1rem] px-[0.25rem] text-[0.625rem] leading-[0.625rem] gap-[0.2rem]',
        'sm' => 'h-[1.25rem] min-w-[1.25rem] px-[0.325rem] text-2xs leading-[0.75rem] gap-1',
    ];

    $colors = [
        'gray' => 'bg-gray-100 text-gray-500',
        'primary' => 'bg-primary-light text-primary',
        'success' => 'bg-success-light text-success',
        'danger' => 'bg-danger-light text-danger',
        'warning' => 'bg-warning-light text-warning',
        'info' => 'bg-info-light text-info',
    ];

    $sizeClasses = $sizes[$size] ?? $sizes['sm'];
    $colorClasses = $colors[$color] ?? $colors['gray'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center justify-center shrink-0 font-medium rounded-sm {$sizeClasses} {$colorClasses}"]) }}>
    {{ $slot }}
</span>
