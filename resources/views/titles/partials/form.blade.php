<div class="form-group">
    <label>Title Name:</label>
    <input type="text" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" name="name" placeholder="Enter title name" value="{{ $title->name ?? old('name') }}">
    @if ($errors->has('name'))
        <div id="name-error" class="error invalid-feedback">{{ $errors->first('name') }}</div>
    @endif
</div>
<div class="form-group input-group flatpickr">
    <label>Date Introduced:</label>
    <div class="input-group flatpickr kt-input-icon kt-input-icon--right">
        <input type="text" class="form-control {{ $errors->has('introduced_at') ? 'is-invalid' : '' }}" data-datetimepicker data-input name="introduced_at" placeholder="Enter date introduced" value="{{ $title->introduced_at ?? old('introduced_at') }}">
        <span class="kt-input-icon__icon kt-input-icon__icon--right">
            <span><i class="flaticon-calendar-with-a-clock-time-tools"></i></span>
        </span>
        @if ($errors->has('introduced_at'))
            <div id="introduced_at-error" class="error invalid-feedback">{{ $errors->first('introduced_at') }}</div>
        @endif
    </div>
</div>
