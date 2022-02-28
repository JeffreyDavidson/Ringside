<label class="form-label" for="{{ $name }}">{{ $label }}:</label>

<textarea
    class="form-control @error($name) is-invalid @enderror"
    name="{{ $name }}"
    :placeholder="$label ?? Enter {{ $label }} : null"
    style="height: 100px"
>
    {{ $value }}
</textarea>

@error($name)
    <x-form.validation-error name="{{ $name }}" :message="$message" />
@enderror
