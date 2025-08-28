@props([
    'name' => null,
    'label' => null,
    'description' => null,
    'variant' => 'block',
    'type' => 'text',
    'size' => null,
])

@php
// Extract name from wire:model if not provided (Flux pattern)
$fieldName = $name ?? $attributes->whereStartsWith('wire:model')->first();
if ($fieldName && str_contains($fieldName, '=')) {
    $fieldName = str($fieldName)->after('=')->trim('"\'')->toString();
}

// Generate ID
$inputId = $attributes->get('id', $fieldName);

// Build input classes with Metronic styling
$inputClasses = collect([
    // Base classes
    'block w-full appearance-none shadow-none outline-none',
    'font-medium text-2sm leading-4',
    'rounded-md h-10 px-3',
    'border border-solid transition-colors',
    // Metronic color scheme
    'bg-gray-50 border-gray-300 text-gray-700',
    'focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary',
    // Size variants
    $size === 'sm' ? 'h-8 px-2 text-xs' : null,
    $size === 'lg' ? 'h-12 px-4 text-base' : null,
])->filter()->implode(' ');

// Forward all attributes except field-specific ones
$inputAttributes = $attributes->except(['label', 'description', 'variant', 'name', 'size']);
@endphp

@if($label || $description)
    {{-- Shorthand mode: auto-wrap in field (Flux pattern) --}}
    <x-form.with-field 
        :label="$label" 
        :description="$description" 
        :variant="$variant" 
        :name="$fieldName">
        <input 
            {{ $inputAttributes->merge([
                'type' => $type,
                'name' => $fieldName,
                'id' => $inputId,
                'class' => $inputClasses
            ]) }} />
    </x-form.with-field>
@else
    {{-- Verbose mode: just the input --}}
    <input 
        {{ $inputAttributes->merge([
            'type' => $type,
            'name' => $fieldName,
            'id' => $inputId,
            'class' => $inputClasses
        ]) }} />
@endif