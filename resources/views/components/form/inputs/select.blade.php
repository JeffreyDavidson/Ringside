@props([
    'name',
    'label',
    'options',
    'selected' => '',
])

<label for="{{ $name }}">{{ $label }}</label>

<select
    name="{{ $name }}"
    {{ $attributes->whereStartsWith('wire:click') }}
    {{ $attributes->whereStartsWith('wire:model') }}
    {{ $attributes->merge(['class' => 'form-select']) }}
>
    <option value="">Select</option>
    @foreach ($options as $key => $value)
        <option
            value="{{ $key }}"
            @selected($selected == $key)
        >{{ $value }}</option>
    @endforeach
</select>

@error($name)
<x-form.validation-error name="{{ $name }}" :message="$message"/>
@enderror
