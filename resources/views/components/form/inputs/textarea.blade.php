@props([
    'placeholder',
    'name',
    'label',
    'value'
])

<label class="form-label" for="{{ $name }}">{{ $label }}:</label>

<textarea
    class="form-control"
    name="{{ $name }}"
    :placeholder="$label ?? Enter {{ $label }} : null"
    style="height: 100px"
>
@isset($value){{ $value }}@endisset
</textarea>

@error($name)
    <x-form.validation-error name="{{ $name }}" :message="$message" />
@enderror
