<label for="{{ $name }}">{{ $label }}:</label>

<input
    type="number"
    class="form-control @error($name) is-invalid @enderror"
    min="{{ $min ?? '' }}"
    max="{{ $max ?? '' }}"
    name="{{ $name }}"
    placeholder="Enter {{ $label }}"
    value="{{ $value }}"
>

@error($name)
    <x-form.validation-error name="{{ $name }}" :message="$message" />
@enderror
