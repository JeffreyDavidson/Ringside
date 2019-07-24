<div class="form-group">
    <label>First Name:</label>
    <input type="text"
        class="form-control @error('first_name') is-invalid @enderror"
        name="first_name"
        placeholder="Enter first name"
        value="{{ old('first_name', $manager->first_name) }}"
    >
    @error('first_name')
        <div id="first-name-error" class="error invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="form-group">
    <label>Last Name:</label>
    <input type="text"
        class="form-control @error('last_name') is-invalid @enderror"
        name="last_name"
        placeholder="Enter last name"
        value="{{ old('last_name', $manager->last_name) }}"
    >
    @error('last_name')
        <div id="last-name-error" class="error invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="form-group">
    <label>Date Started:</label>
    <div class="kt-input-icon kt-input-icon--right">
        <input type="text"
            class="form-control @error('started_at') is-invalid @enderror"
            data-datetimepicker
            data-input
            name="started_at"
            placeholder="Enter date started"
            value="{{ old('started_at', optional($manager->employment->started_at ?? null)->toDateTimeString()) }}"
        >
        <span class="kt-input-icon__icon kt-input-icon__icon--right">
            <span><i class="flaticon-calendar-with-a-clock-time-tools"></i></span>
        </span>
        @error('started_at')
            <div id="started-at-error" class="error invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
