
<label class="form-label" for="{{ $name }}">{{ $label }}</label>

<input
    type="text"
    {{ $attributes->merge([
        'class' => 'form-control',
        'placeholder' => 'Enter '.($label ?? 'Value'),
    ]) }}
>

@error($name)
    <x-form.validation-error name="{{ $name }}" :message="$message" />
@enderror
