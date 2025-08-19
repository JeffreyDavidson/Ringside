@props([
    'size' => null,               // null (default), 'sm', 'lg'
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

        // Size classes for text buttons
        'h-8 px-3 text-sm gap-1' => $size === 'sm' && !$iconOnly,
        'h-10 px-4 text-sm gap-1.5' => !$size && !$iconOnly,          // Default size
        'h-12 px-5 text-base gap-2' => $size === 'lg' && !$iconOnly,

        // Icon-only sizes (square)
        'w-8 h-8' => $size === 'sm' && $iconOnly,
        'w-10 h-10' => !$size && $iconOnly,                           // Default size
        'w-12 h-12' => $size === 'lg' && $iconOnly,

        // Filled style combinations
        'bg-[var(--primary)] text-[var(--primary-foreground)] hover:bg-[var(--primary)]/90 hover:text-[var(--primary-foreground)]' => $style === 'filled' && $variant === 'primary',
        'bg-[var(--secondary)] text-[var(--secondary-foreground)] hover:bg-[var(--secondary)]/90 hover:text-[var(--foreground)]' => $style === 'filled' && $variant === 'secondary',
        'bg-[var(--destructive)] text-[var(--destructive-foreground)] hover:bg-[var(--destructive)]/90' => $style === 'filled' && $variant === 'destructive',
        'bg-[var(--mono)] text-[var(--mono-foreground)] hover:bg-[var(--mono)]/90 hover:text-[var(--mono-foreground)]' => $style === 'filled' && $variant === 'mono',

        // Outline style combinations
        'border border-[var(--primary)] text-[var(--primary)] bg-transparent hover:bg-[var(--primary)] hover:text-[var(--primary-foreground)]' => $style === 'outline' && $variant === 'primary',
        'border border-[var(--input)] text-[var(--secondary-foreground)] bg-transparent hover:bg-[var(--accent)] hover:text-[var(--accent-foreground)]' => $style === 'outline' && $variant === 'default',
        'border border-[var(--destructive)] text-[var(--destructive)] bg-transparent hover:bg-[var(--destructive)] hover:text-[var(--destructive-foreground)]' => $style === 'outline' && $variant === 'destructive',

        // Ghost style combinations
        'text-[var(--primary)] hover:text-[var(--primary-foreground)] hover:bg-[var(--primary)]' => $style === 'ghost' && $variant === 'primary',
        'text-[var(--accent-foreground)] hover:bg-[var(--accent)] hover:text-[var(--accent-foreground)]' => $style === 'ghost' && $variant === 'default',
        'text-[var(--destructive)] hover:text-[var(--destructive-foreground)] hover:bg-[var(--destructive)]' => $style === 'ghost' && $variant === 'destructive',
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
