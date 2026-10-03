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
        'gray' => 'border-ringside-line bg-ringside-surface-hover text-ringside-muted',
        'danger' => 'border-ringside-signal-soft/40 bg-ringside-signal-soft/12 text-ringside-signal-soft',
        'warning' => 'border-ringside-warning/40 bg-ringside-warning/12 text-ringside-warning',
    ];

    $sizeClasses = $sizes[$size] ?? $sizes['sm'];
    $colorClasses = $colors[$color] ?? $colors['gray'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center justify-center shrink-0 border font-medium {$sizeClasses} {$colorClasses}"]) }}>
    {{ $slot }}
</span>
