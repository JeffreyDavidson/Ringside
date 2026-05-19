@props([
    'name' => null,
    'label' => null,
    'description' => null,
    'variant' => 'block',
    'size' => 'md',
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'multiple' => false,
])

@php
    $fieldName = $name ?? $attributes->whereStartsWith('wire:model')->first();
    if ($fieldName && str_contains($fieldName, '=')) {
        $fieldName = str($fieldName)->after('=')->trim('"\'')->toString();
    }

    $inputId = $attributes->get('id', $fieldName);

    $selectClasses = collect([
        'block w-full appearance-none outline-none',
        'border border-solid border-[var(--input)] bg-background text-foreground',
        'rounded-[calc(var(--radius)-2px)] shadow-[var(--tw-input-box-shadow)] transition-[color,box-shadow]',
        'focus-visible:outline-none focus-visible:border-[var(--ring)] focus-visible:ring-2 focus-visible:ring-[color-mix(in_oklab,var(--ring)_30%,transparent)]',
        $size === 'sm' ? 'h-[calc(var(--spacing)*7)] px-[calc(var(--spacing)*2.5)] text-xs' : null,
        $size === 'md' ? 'h-[calc(var(--spacing)*8.5)] px-[calc(var(--spacing)*3)] text-2sm' : null,
        $size === 'lg' ? 'h-[calc(var(--spacing)*10)] px-[calc(var(--spacing)*4)] text-sm' : null,
    ])->filter()->implode(' ');

    $selectAttributes = $attributes->except(['label', 'description', 'variant', 'name', 'size', 'options', 'selected', 'placeholder', 'multiple']);

    $selectedValues = is_array($selected) ? $selected : ($selected !== null ? [$selected] : []);
@endphp

@if($label || $description)
    <x-form.with-field
        :label="$label"
        :description="$description"
        :variant="$variant"
        :name="$fieldName">
        <select
            {{ $selectAttributes->merge([
                'name' => $multiple ? "{$fieldName}[]" : $fieldName,
                'id' => $inputId,
                'class' => $selectClasses,
                'multiple' => $multiple ?: null,
            ]) }}>
            @if($placeholder && !$multiple)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach($options as $value => $optionLabel)
                <option value="{{ $value }}" @selected(in_array($value, $selectedValues))>{{ $optionLabel }}</option>
            @endforeach
        </select>
    </x-form.with-field>
@else
    <select
        {{ $selectAttributes->merge([
            'name' => $multiple ? "{$fieldName}[]" : $fieldName,
            'id' => $inputId,
            'class' => $selectClasses,
            'multiple' => $multiple ?: null,
        ]) }}>
        @if($placeholder && !$multiple)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach($options as $value => $optionLabel)
            <option value="{{ $value }}" @selected(in_array($value, $selectedValues))>{{ $optionLabel }}</option>
        @endforeach
    </select>
@endif
