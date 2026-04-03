@props([
    'name' => null,
    'label' => null,
    'description' => null,
    'variant' => 'block',
    'size' => 'md',
    'rows' => 4,
])

@php
    $fieldName = $name ?? $attributes->whereStartsWith('wire:model')->first();
    if ($fieldName && str_contains($fieldName, '=')) {
        $fieldName = str($fieldName)->after('=')->trim('"\'')->toString();
    }

    $inputId = $attributes->get('id', $fieldName);

    $textareaClasses = collect([
        'block w-full appearance-none outline-none resize-y',
        'border border-solid border-[var(--input)] bg-background text-foreground',
        'rounded-[calc(var(--radius)-2px)] shadow-[var(--tw-input-box-shadow)] transition-[color,box-shadow]',
        'placeholder-[var(--muted-foreground)]',
        'focus-visible:outline-none focus-visible:border-[var(--ring)] focus-visible:ring-2 focus-visible:ring-[color-mix(in_oklab,var(--ring)_30%,transparent)]',
        $size === 'sm' ? 'px-[calc(var(--spacing)*2.5)] py-[calc(var(--spacing)*1.5)] text-xs' : null,
        $size === 'md' ? 'px-[calc(var(--spacing)*3)] py-[calc(var(--spacing)*2)] text-2sm' : null,
        $size === 'lg' ? 'px-[calc(var(--spacing)*4)] py-[calc(var(--spacing)*2.5)] text-sm' : null,
    ])->filter()->implode(' ');

    $textareaAttributes = $attributes->except(['label', 'description', 'variant', 'name', 'size', 'rows']);
@endphp

@if($label || $description)
    <x-form.with-field
        :label="$label"
        :description="$description"
        :variant="$variant"
        :name="$fieldName">
        <textarea
            {{ $textareaAttributes->merge([
                'name' => $fieldName,
                'id' => $inputId,
                'rows' => $rows,
                'class' => $textareaClasses,
            ]) }}>{{ $slot }}</textarea>
    </x-form.with-field>
@else
    <textarea
        {{ $textareaAttributes->merge([
            'name' => $fieldName,
            'id' => $inputId,
            'rows' => $rows,
            'class' => $textareaClasses,
        ]) }}>{{ $slot }}</textarea>
@endif
