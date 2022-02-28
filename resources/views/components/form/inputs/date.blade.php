<label for="{{ $name }}">{{ $label }}:</label>

<div class="kt-input-icon kt-input-icon--right">
    <input type="datetime-local"
        class="form-control @error($name) is-invalid @enderror"
        name="{{ $name }}"
        :placeholder="$label ?? Enter {{ $label }} : null"
        value="{{ $value }}">
    <span class="kt-input-icon__icon kt-input-icon__icon--right">
        <span><i class="flaticon-calendar-with-a-clock-time-tools"></i></span>
    </span>
    @error($name)
        <x-form.validation-error name="{{ $name }}" :message="$message" />
    @enderror
</div>
