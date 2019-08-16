@filter
    <div class="kt-nav__item">
        <div class="form-group m-3">
            <label>Status:</label>
            <select class="form-control" name="status" id="status-dropdown">
                <option value="">Select</option>
                @foreach (\App\Enums\RefereeStatus::labels() as $value => $label)
                    <option value="{{ $value }}"> {{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="kt-nav__item">
        <div class="form-group m-3">
            <label>Started Between:</label>
            <div class="input-group flatpickr kt-input-icon kt-input-icon--right">
                <input class="form-control flatpickr-input" placeholder="Start Date" id="started_at_start" data-datetimepicker="" type="text" readonly="readonly">
                <span class="kt-input-icon__icon kt-input-icon__icon--right">
                    <span><i class="flaticon-calendar-with-a-clock-time-tools"></i></span>
                </span>
            </div>
            <small class="text-center font-weight-bold text-muted d-block">to</small>
            <div class="input-group flatpickr kt-input-icon kt-input-icon--right">
                <input class="form-control flatpickr-input" placeholder="End Date" id="started_at_end" data-datetimepicker="" type="text" readonly="readonly">
                <span class="kt-input-icon__icon kt-input-icon__icon--right">
                    <span><i class="flaticon-calendar-with-a-clock-time-tools"></i></span>
                </span>
            </div>
        </div>
    </div>
@endfilter
