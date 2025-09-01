@props([
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? '',
    'label' => null,
    'size' => 'md',               // 'sm', 'md' (default), 'lg'
])

@php
// Build consistent checkbox classes using Metronic specifications
$checkboxClasses = collect([
    // Base Metronic checkbox classes
    'cursor-pointer appearance-none border border-solid border-input bg-background rounded-[calc(var(--radius)-4px)] flex-shrink-0',
    'focus-visible:outline-none focus-visible:border-[var(--ring)] focus-visible:ring-2 focus-visible:ring-[color-mix(in_oklab,var(--ring)_30%,transparent)]',
    'checked:bg-primary checked:border-primary',
    // Size variants (Metronic specifications)
    $size === 'sm' ? 'w-4.5 h-4.5' : null,     // 18px × 18px
    $size === 'md' ? 'w-5 h-5' : null,         // 20px × 20px  
    $size === 'lg' ? 'w-5.5 h-5.5' : null,     // 22px × 22px
])->filter()->implode(' ');
@endphp

@if($label)
    <label class="inline-flex items-center gap-2 text-sm leading-none font-medium text-foreground" for="{{ $name }}">
        <input type="checkbox"
            {{ $attributes->merge([
                    'id' => $name,
                    'name' => $name,
                    'class' => $checkboxClasses
                ]) }}>
        <span>{{ $label }}</span>
    </label>
@else
    <input type="checkbox"
        {{ $attributes->merge([
                'id' => $name,
                'name' => $name,
                'class' => $checkboxClasses
            ]) }}>
@endif

<x-form.error name="{{ $name }}" />
