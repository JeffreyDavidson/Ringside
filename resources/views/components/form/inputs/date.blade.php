<label class="form-label" for="{{ $name }}">{{ $label }}</label>
<input type="date" {{ $attributes }}
 name="{{ $name }}" class="form-control" placeholder="MM-DD-YYYY" >
@error($name)
    <x-form.validation-error name="{{ $name }}" :message="$message" />
@enderror
