<label for="{{ $name }}">{{ $label }}</label>

<input
    type="number"
    class="form-control"
    min="{{ $min ?? 0 }}"
    max="{{ $max ?? '' }}"
    name="{{ $name }}"
    placeholder="{{ $placeholder ?? '' }}"
    value="{{ $value ?? null }}"
>

@error($name)
    <x-form.validation-error name="{{ $name }}" :message="$message" />
@enderror
