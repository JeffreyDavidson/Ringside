@props([
    'size' => 'md',               // 'sm', 'md' (default), 'lg'
    'style' => 'filled',          // filled (default), outline, ghost
    'variant' => 'primary',       // primary, secondary, destructive, mono
    'iconLeft' => null,
    'iconRight' => null,
    'iconOnly' => false,
])

<button
    {{ $attributes->class([
        // Base classes
        'inline-flex items-center justify-center cursor-pointer font-medium whitespace-nowrap transition-colors duration-200 rounded-md',

        // Size classes for text buttons (Metronic specifications)
        'h-7 px-2.5 text-xs gap-1.5' => $size === 'sm' && !$iconOnly,
        'h-8.5 px-3 text-2sm gap-1.5' => $size === 'md' && !$iconOnly,        // Default size
        'h-10 px-4 text-sm gap-1.5' => $size === 'lg' && !$iconOnly,

        // Icon-only size (consistent across all sizes) - Metronic .kt-btn-icon
        'w-8.5 h-8.5 p-0' => $iconOnly,

        // Filled style combinations
        'bg-primary text-white hover:bg-primary/90' => $style === 'filled' && $variant === 'primary',
        'bg-secondary text-foreground hover:bg-secondary/90' => $style === 'filled' && $variant === 'secondary',
        'bg-destructive text-white hover:bg-destructive/90' => $style === 'filled' && $variant === 'destructive',
        'bg-foreground text-background hover:bg-foreground/90' => $style === 'filled' && $variant === 'mono',

        // Outline style combinations
        'border border-primary text-primary bg-transparent hover:bg-primary hover:text-white' => $style === 'outline' && $variant === 'primary',
        'border border-input text-muted-foreground bg-transparent hover:bg-muted hover:text-foreground' => $style === 'outline' && $variant === 'default',
        'border border-destructive text-destructive bg-transparent hover:bg-destructive hover:text-white' => $style === 'outline' && $variant === 'destructive',

        // Ghost style combinations
        'text-primary hover:text-white hover:bg-primary' => $style === 'ghost' && $variant === 'primary',
        'text-muted-foreground hover:bg-muted hover:text-foreground' => $style === 'ghost' && $variant === 'default',
        'text-destructive hover:text-white hover:bg-destructive' => $style === 'ghost' && $variant === 'destructive',
    ]) }}>

    @if($iconLeft && !$iconOnly)
        <x-ui.icon name="{{ $iconLeft }}" />
    @endif

    @if($iconOnly)
        <x-ui.icon name="{{ $iconLeft ?? $iconRight }}" />
    @else
        {{ $slot }}
    @endif

    @if($iconRight && !$iconOnly)
        <x-ui.icon name="{{ $iconRight }}" />
    @endif
</button>
