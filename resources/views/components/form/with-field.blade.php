@props([
    'label' => null,
    'description' => null,
    'variant' => 'block',
    'name' => null,
])

@php
// Temporary minimal implementation for testing
$classes = collect()
    ->add('min-w-0')
    ->when($variant === 'block', fn($classes) => $classes->add('flex flex-col gap-1'))
    ->implode(' ');
@endphp

<x-form.field :variant="$variant" {{ $attributes->except(['label', 'description', 'name']) }}>
    @if($label)
        <x-form.label>{{ $label }}</x-form.label>
    @endif
    
    <div data-form-control>
        {{ $slot }}
    </div>
    
    @if($description)
        <x-form.description>{{ $description }}</x-form.description>
    @endif
    
    @if($name)
        <x-form.error :name="$name" />
    @endif
</x-form.field>