@props([
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? '',
    'label' => 'Value',
    'placeholder' => 'MM-DD-YYYY',
])

<x-form.form-label :$name :$label />

<label class="form-input-container">
    <input type="date"
        {{ $attributes->merge([
                'id' => $name,
                'name' => $name,
                'placeholder' => $placeholder,
            ])->class([
                'form-input-element',
            ]) }}>
</label>

@error($name)
    <x-form.validation-error name="{{ $name }}" :message="$message" />
@enderror
