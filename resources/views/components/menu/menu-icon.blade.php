@props([
    'icon' => 'squares-2x2',
])

@php
    // Map ki- icons to heroicon names
    $iconMap = [
        'ki-home' => 'home',
        'ki-people' => 'users', 
        'ki-cup' => 'trophy',
        'ki-home-3' => 'building-office',
        'ki-calendar' => 'calendar-days',
        'ki-element-11' => 'squares-2x2',
    ];
    
    $heroIcon = $iconMap[$icon] ?? str_replace('ki-', '', $icon);
@endphp

<span {{ $attributes->merge(['class' => 'flex shrink-0']) }}>
    <x-dynamic-component :component="'heroicon-s-' . $heroIcon" class="size-5" />
</span>
