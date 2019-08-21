@filter
    <div class="kt-nav__item">
        <div class="form-group m-3">
            <label>Status:</label>
            @statusSelect(['statuses' => \App\Enums\RefereeStatus::labels()])
            @endstatusSelect
        </div>
    </div>
    <div class="kt-nav__item">
        <div class="form-group m-3">
            <label>Started Between:</label>
            @flatpickr
            @endflatpickr
        </div>
    </div>
@endfilter
