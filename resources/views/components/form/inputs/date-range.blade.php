<label for="{{ $name }}">{{ $label }}:</label>

<div class="input-group flatpickr kt-input-icon kt-input-icon--right">
    <div class="kt-input-icon kt-input-icon--right">
        {{-- <x-form.inputs.text
            id="{{$name}}_start"
            name="{$name}.start"
            data-datetimepicker
            data-input
        /> --}}
        <input
            type="text"
            class="form-control @error("{$name}.start") is-invalid @enderror"
            name="{{ $name }}[start]"
            id="{{ $name }}_start"
            placeholder="Enter {{ $label }} Start"
            data-datetimepicker
            data-input
        >
        <span class="kt-input-icon__icon kt-input-icon__icon--right">
            <span><i class="flaticon-calendar-with-a-clock-time-tools"></i></span>
        </span>
        @error("{$name}.start")
            <x-form.validation-error name="{{ $name }}['start']" :message="$message" />
        @enderror
    </div>
</div>
<small class="text-center font-weight-bold text-muted d-block">to</small>
<div class="input-group flatpickr kt-input-icon kt-input-icon--right">
    <div class="kt-input-icon kt-input-icon--right">
        <input
            type="text"
            class="form-control @error("{$name}.end") is-invalid @enderror"
            name="{{ $name }}[end]"
            id="{{ $name }}_end"
            placeholder="Enter {{ $label }} End"
            data-datetimepicker
            data-input
        >
        <span class="kt-input-icon__icon kt-input-icon__icon--right">
            <span><i class="flaticon-calendar-with-a-clock-time-tools"></i></span>
        </span>
        @error("{$name}.end")
            <x-form.validation-error name="{{ $name }}['end']" :message="$message" />
        @enderror
    </div>
</div>
