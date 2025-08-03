@props([
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? '',
    'label' => 'Value',
    'placeholder' => 'Enter ' . $label,
])

<x-form.form-label :$name :$label />

<div class="form-input-container">
    <input type="text"
        {{ $attributes->merge([
                'id' => $name,
                'name' => $name,
                'placeholder' => $placeholder,
            ])->class([
                'form-input-element',
            ]) }}>
</div>

@error($name)
    <x-form.validation-error name="{{ $name }}" :message="$message" />
@enderror
