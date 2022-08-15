<label class="form-label" for="{{ $name }}">{{ $label }}:</label>

<input
    type="text"
    class="form-control"
    name="{{ $name }}"
    :placeholder="$label ?? Enter {{ $label }} : null"
    value="{{ $value ?? null }}"
>

@error($name)
    <x-form.validation-error name="{{ $name }}" :message="$message" />
@enderror
