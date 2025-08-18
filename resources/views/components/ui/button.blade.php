@props([
    'size' => null,               // null (default), 'sm', 'lg'
    'variant' => 'primary',       // primary, secondary, destructive, mono, outline, ghost, ghost-primary, ghost-secondary, ghost-destructive, ghost-mono
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
        
        // Primary variant
        'bg-primary text-primary-inverse hover:bg-primary-active shadow-sm' => $variant === 'primary',
        
        // Secondary variant
        'bg-secondary text-secondary-inverse hover:bg-secondary-active shadow-sm' => $variant === 'secondary',
        
        // Destructive variant
        'bg-danger text-danger-inverse hover:bg-danger-active shadow-sm' => $variant === 'destructive',
        
        // Mono variant
        'bg-gray-900 text-white hover:bg-gray-800 shadow-sm' => $variant === 'mono',
        
        // Outline variant
        'border border-gray-300 text-gray-700 bg-transparent hover:bg-gray-50' => $variant === 'outline',
        
        // Ghost variant (standalone)
        'text-gray-700 hover:bg-gray-100' => $variant === 'ghost',
        
        // Ghost + Primary combination
        'text-primary hover:bg-primary-light' => $variant === 'ghost-primary',
        
        // Ghost + Secondary combination
        'text-secondary hover:bg-secondary-light' => $variant === 'ghost-secondary',
        
        // Ghost + Destructive combination
        'text-danger hover:bg-danger-light' => $variant === 'ghost-destructive',
        
        // Ghost + Mono combination
        'text-gray-900 hover:bg-gray-100' => $variant === 'ghost-mono',
    ]) }}>
    
    @if($iconLeft && !$iconOnly)
        <i class="{{ $iconLeft }}"></i>
    @endif
    
    @if($iconOnly)
        <i class="{{ $iconLeft ?? $iconRight }}"></i>
    @else
        {{ $slot }}
    @endif
    
    @if($iconRight && !$iconOnly)
        <i class="{{ $iconRight }}"></i>
    @endif
</button>