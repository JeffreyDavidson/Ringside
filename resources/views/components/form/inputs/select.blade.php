@props([
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? '',
    'label' => 'Value',
    'options' => [],
    'selected' => '',
])

<x-form.form-label :$name :$label />

<select
    {{ $attributes->merge([
            'id' => $name,
            'name' => $name,
        ])->class([
            'form-input-base form-input-default form-input-states',
        ]) }}>
    <option value="">Select</option>
    @foreach ($options as $key => $value)
        <option value="{{ $key }}" @selected($selected == $key)>{{ $value }}</option>
    @endforeach
</select>

@error($name)
    <x-form.validation-error name="{{ $name }}" :message="$message" />
@enderror
