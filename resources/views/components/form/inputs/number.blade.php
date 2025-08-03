@props([
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? '',
    'label' => 'Value',
    'placeholder' => 'Enter ' . $label,
    'min' => 0,
    'max' => null,
])

<x-form.form-label :$name :$label />

<input type="number"
    {{ $attributes->merge([
            'id' => $name,
            'name' => $name,
            'placeholder' => $placeholder,
            'min' => $min,
            'max' => $max,
        ])->class([
            'form-input-base form-input-default form-input-states',
        ]) }}>

@error($name)
    <x-form.validation-error name="{{ $name }}" :message="$message" />
@enderror
