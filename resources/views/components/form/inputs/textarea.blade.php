@props([
    'name' => null,
    'label' => null,
    'description' => null,
    'variant' => 'block',
    'size' => 'md',
    'rows' => 4,
])

@php
    $field = \App\View\Forms\FormFieldContext::from($name, $attributes, $errors);
    $fieldName = $field->name;
    $inputId = $field->id;
    $describedBy = $field->describedBy;

    $textareaClasses = collect([
        'block w-full appearance-none outline-none resize-y',
        'border border-solid border-ringside-outline bg-ringside-surface-panel text-ringside-ink aria-invalid:border-ringside-signal-soft',
        'rounded-none transition-colors',
        'placeholder:text-ringside-muted',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ringside-white',
        $size === 'sm' ? 'px-[calc(var(--spacing)*2.5)] py-[calc(var(--spacing)*1.5)] text-xs' : null,
        $size === 'md' ? 'px-[calc(var(--spacing)*3)] py-[calc(var(--spacing)*2)] text-2sm' : null,
        $size === 'lg' ? 'px-[calc(var(--spacing)*4)] py-[calc(var(--spacing)*2.5)] text-sm' : null,
    ])->filter()->implode(' ');

    $textareaAttributes = $attributes->except(['label', 'description', 'variant', 'name', 'size', 'rows', 'aria-describedby', 'aria-invalid']);
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
        <textarea
            {{
                $textareaAttributes->merge([
                    'name' => $fieldName,
                    'id' => $inputId,
                    'rows' => $rows,
                    'class' => $textareaClasses,
                    'aria-invalid' => $field->hasError ? 'true' : null,
                    'aria-describedby' => $describedBy ?: null,
                ])
            }}
        >{{ $slot }}</textarea>
    </x-form.with-field>
@else
    <textarea
        {{
            $textareaAttributes->merge([
                'name' => $fieldName,
                'id' => $inputId,
                'rows' => $rows,
                'class' => $textareaClasses,
                'aria-invalid' => $field->hasError ? 'true' : null,
                'aria-describedby' => $describedBy ?: null,
            ])
        }}
    >{{ $slot }}</textarea>
@endif
