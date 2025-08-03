@aware([
    'isDefault' => false,
])

@props([
    'icon' => ''
])

@php
    // Map ki- icons to heroicon names
    $iconMap = [
        'ki-trash' => 'trash',
        'ki-pencil' => 'pencil',
        'ki-magnifying-glass' => 'magnifying-glass',
        'ki-search-list' => 'magnifying-glass',
        'ki-dots-vertical' => 'ellipsis-vertical',
    ];
    
    $heroIcon = $iconMap[$icon] ?? str_replace('ki-', '', $icon);
@endphp

<span {{ $attributes->merge(['class' => 'flex items-center shrink-0'])->class([
    'me-2.5' => $isDefault,
]) }}>
    @if($heroIcon)
        <x-dynamic-component :component="'heroicon-s-' . $heroIcon" class="size-4" />
    @endif
</span>
