@props([
    'name' => null,
    'description' => null, 
    'label' => null,
    'variant' => 'block',
])

@php
// Extract name from wire:model if not provided (Flux pattern)
$fieldName = $name ?? $attributes->whereStartsWith('wire:model')->first();
if ($fieldName && str_contains($fieldName, '=')) {
    $fieldName = str($fieldName)->after('=')->trim('"\'')->toString();
}

// Forward attributes excluding field-specific ones
$fieldAttributes = $attributes->only(['variant', 'class']);
$slotAttributes = $attributes->except(['name', 'description', 'label', 'variant']);
@endphp

@if($label || $description)
    <x-form.field :variant="$variant" {{ $fieldAttributes }}>
        @if($label)
            <x-form.label :for="$fieldName" data-form-label>
                {{ $label }}
            </x-form.label>
        @endif
        
        @if($description && $variant === 'block')
            <x-form.description data-form-description>
                {{ $description }}
            </x-form.description>
        @endif
        
        <div data-form-control>
            {{ $slot->withAttributes($slotAttributes->merge(['name' => $fieldName])) }}
        </div>
        
        <x-form.error :name="$fieldName" data-form-error />
        
        @if($description && $variant === 'inline')
            <x-form.description data-form-description>
                {{ $description }}
            </x-form.description>
        @endif
    </x-form.field>
@else
    {{ $slot->withAttributes($slotAttributes->merge(['name' => $fieldName])) }}
    @if($fieldName)
        <x-form.error :name="$fieldName" />
    @endif
@endif