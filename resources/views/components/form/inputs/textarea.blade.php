@props([
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? '',
    'label' => 'Value',
    'placeholder' => 'Enter ' . $label,
])

<x-form.form-label :$name :$label />

<textarea
    {{ $attributes->merge([
            'id' => $name,
            'name' => $name,
            'placeholder' => $placeholder,
        ])->class([
            'form-input-base form-input-textarea form-input-states',
        ]) }}>
</textarea>

@error($name)
    <x-form.validation-error name="{{ $name }}" :message="$message" />
@enderror
