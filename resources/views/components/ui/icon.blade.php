@props([
    'name' => null,                    // Icon name (e.g., 'plus', 'pencil', 'trash')
    'style' => 'outline',              // filled, duotone, outline, solid
    'variant' => null,                 // primary, danger, warning, success, null (default)
    'size' => 'md',                    // sm, md, lg, xl, 2xl, 3xl
])

@php
    $iconClass = 'ki-' . $name;
    
    // Add style class
    if ($style === 'duotone') {
        $iconClass .= ' ki-duotone';
    } elseif ($style === 'filled') {
        $iconClass .= ' ki-filled';
    } elseif ($style === 'solid') {
        $iconClass .= ' ki-solid';
    } else {
        $iconClass .= ' ki-outline';
    }
@endphp

<i {{ $attributes->class([
    $iconClass,
    
    // Size classes
    'text-sm' => $size === 'sm',
    'text-base' => $size === 'md',
    'text-lg' => $size === 'lg', 
    'text-xl' => $size === 'xl',
    'text-2xl' => $size === '2xl',
    'text-3xl' => $size === '3xl',
    
    // Variant colors
    'text-primary' => $variant === 'primary',
    'text-danger' => $variant === 'danger', 
    'text-warning' => $variant === 'warning',
    'text-green-500' => $variant === 'success',
]) }}>
    @if($style === 'duotone')
        {{-- Duotone icons have special structure --}}
        <span class="path1"></span>
        <span class="path2"></span>
    @endif
</i>