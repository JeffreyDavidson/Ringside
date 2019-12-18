<field-label :for="$name" :label="$label ?? ''" />

<div class="kt-input-icon kt-input-icon--right">
    <input 
        type="text" 
        class="form-control @error($name) is-invalid @enderror"
        name="{{ $name }}" 
        placeholder="Enter {{ $label }}"
        value="{{ old($name, $model->name) }}"
        data-datetimepicker 
        data-input
    >
    <span class="kt-input-icon__icon kt-input-icon__icon--right">
        <span><i class="flaticon-calendar-with-a-clock-time-tools"></i></span>
    </span>
    @error($name)
        <form-error name="{{ $name }}" />
    @enderror
</div>
