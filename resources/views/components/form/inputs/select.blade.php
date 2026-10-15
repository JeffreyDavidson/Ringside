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
    $field = \App\View\Forms\FormFieldContext::from($name, $attributes, $errors);
    $fieldName = $field->name;
    $inputId = $field->id;
    $describedBy = $field->describedBy;

    $selectClasses = collect([
        'block w-full appearance-none outline-none',
        'border border-solid border-outline bg-surface-panel text-foreground aria-invalid:border-error',
        'rounded-none transition-colors',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-foreground',
        $multiple ? 'h-auto min-h-28 px-3 py-2 text-2sm' : null,
        ! $multiple && $size === 'sm' ? 'h-7 px-2.5 text-xs' : null,
        ! $multiple && $size === 'md' ? 'h-8.5 px-3 text-2sm' : null,
        ! $multiple && $size === 'lg' ? 'h-10 px-4 text-sm' : null,
    ])->filter()->implode(' ');

    $selectAttributes = $attributes->except(['label', 'description', 'variant', 'name', 'size', 'options', 'selected', 'placeholder', 'multiple', 'aria-describedby', 'aria-invalid']);

    $selectedValues = is_array($selected) ? $selected : ($selected !== null ? [$selected] : []);
@endphp

@if ($label || $description)
    <x-form.with-field
        :label="$label"
        :description="$description"
        :variant="$variant"
        :name="$fieldName"
        :id="$inputId"
        :required="(bool) $attributes->get('required', false)"
    >
        <select {{
            $selectAttributes->merge([
                'name' => $multiple ? "{$fieldName}[]" : $fieldName,
                'id' => $inputId,
                'class' => $selectClasses,
                'multiple' => $multiple ?: null,
                'aria-invalid' => $field->hasError ? 'true' : null,
                'aria-describedby' => $describedBy ?: null,
            ])
        }}>
            @if ($placeholder && ! $multiple)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($options as $value => $optionLabel)
                <option value="{{ $value }}" @selected(in_array($value, $selectedValues))>{{ $optionLabel }}</option>
            @endforeach
        </select>
    </x-form.with-field>
@else
    <select {{
        $selectAttributes->merge([
            'name' => $multiple ? "{$fieldName}[]" : $fieldName,
            'id' => $inputId,
            'class' => $selectClasses,
            'multiple' => $multiple ?: null,
            'aria-invalid' => $field->hasError ? 'true' : null,
            'aria-describedby' => $describedBy ?: null,
        ])
    }}>
        @if ($placeholder && ! $multiple)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $value => $optionLabel)
            <option value="{{ $value }}" @selected(in_array($value, $selectedValues))>{{ $optionLabel }}</option>
        @endforeach
    </select>
@endif
