@props([
    'size' => 'sm',
    'color' => 'gray',
])

@php
    $sizes = [
        'xs' => 'h-[1rem] min-w-[1rem] px-[0.25rem] text-[0.625rem] leading-[0.625rem] gap-[0.2rem]',
        'sm' => 'h-[1.25rem] min-w-[1.25rem] px-[0.325rem] text-2xs leading-[0.75rem] gap-1',
    ];

    // Each colour keeps at least 5:1 text contrast on every Ringside surface, including hovered table rows.
    $colors = [
        'gray' => 'border-border bg-surface-hover text-muted-foreground',
        'danger' => 'border-signal-soft/40 bg-signal-soft/12 text-signal-soft',
        'warning' => 'border-warning/40 bg-warning/12 text-warning',
    ];

    $sizeClasses = $sizes[$size] ?? $sizes['sm'];
    $colorClasses = $colors[$color] ?? $colors['gray'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center justify-center shrink-0 border font-medium {$sizeClasses} {$colorClasses}"]) }}>
    {{ $slot }}
</span>
