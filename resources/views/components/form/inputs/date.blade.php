<label class="form-label" for="{{ $name }}">{{ $label }}</label>
<input type="date" name="{{ $name }}" class="form-control" placeholder="MM-DD-YYYY" @isset($value) value="{{ $value }}" @endisset>
@error($name)
    <x-form.validation-error name="{{ $name }}" :message="$message" />
@enderror
