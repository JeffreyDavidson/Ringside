@props([
    'span' => '12',
    'sm' => null,
    'md' => null,
    'lg' => null,
    'xl' => null,
    'gap' => '4',
    'class' => '',
])

@php
    $classes = ['flex-shrink-0', 'px-' . $gap];
    
    // Base column width
    $baseWidth = $span ? 'w-' . $span . '/12' : 'w-full';
    $classes[] = $baseWidth;
    
    // Responsive widths
    if ($sm) $classes[] = 'sm:w-' . $sm . '/12';
    if ($md) $classes[] = 'md:w-' . $md . '/12';
    if ($lg) $classes[] = 'lg:w-' . $lg . '/12';
    if ($xl) $classes[] = 'xl:w-' . $xl . '/12';
    
    $classes[] = $class;
    
    $finalClass = implode(' ', array_filter($classes));
@endphp

<div {{ $attributes->merge(['class' => $finalClass]) }}>
    {{ $slot }}
</div>