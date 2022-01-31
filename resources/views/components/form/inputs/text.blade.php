<label class="form-label" for="{{ $name }}">{{ $label }}:</label>

<input type="text"
    class="form-control @error($name) is-invalid @enderror"
    name="{{ $name }}"
    :placeholder="$label ?? Enter {{ $label }} : null"
    value="{{ $value }}"
>

@error($name)
    <x-form.validation-error name="{{ $name }}" :message="$message" />
@enderror
