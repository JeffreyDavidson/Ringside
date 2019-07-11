<div class="kt-section">
    <h3 class="kt-section__title">General Information:</h3>
    <div class="kt-section_content">
        <div class="form-group">
            <label>Name:</label>
            <input type="text"
                class="form-control @error('name') is-invalid @enderror"
                name="name"
                placeholder="Enter name"
                value="{{ old('name', $wrestler->name) }}"
            >
            @error('name')
                <div id="name-error" class="error invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="row">
            <div class="col-lg-4">
                <div class="form-group">
                    <label>Hometown:</label>
                    <input type="text"
                        class="form-control @error('hometown') is-invalid @enderror"
                        name="hometown"
                        placeholder="Enter hometown"
                        value="{{ old('hometown', $wrestler->hometown) }}"
                    >
                    @error('hometown')
                        <div id="hometown-error" class="error invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <label>Date Hired:</label>
                    <div class="kt-input-icon kt-input-icon--right">
                        <input type="text"
                            class="form-control @error('hired_at') is-invalid @enderror"
                            data-datetimepicker
                            data-input
                            name="hired_at"
                            placeholder="Enter date hired"
                            value="{{ old('hired_at', optional($wrestler->hired_at)->toDateTimeString()) }}"
                        >
                        <span class="kt-input-icon__icon kt-input-icon__icon--right">
                            <span><i class="flaticon-calendar-with-a-clock-time-tools"></i></span>
                        </span>
                        @error('hired_at')
                            <div id="hired-at-error" class="error invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <label>Signature Move:</label>
                    <input type="text"
                        class="form-control @error('signature_move') is-invalid @enderror"
                        name="signature_move"
                        placeholder="Enter signature move"
                        value="{{ old('signature_move', $wrestler->signature_move) }}"
                    >
                    @error('signature_move')
                        <div id="signature-move-error" class="error invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>
<div class="kt-section">
    <h3 class="kt-section__title">Physical Information:</h3>
    <div class="kt-section_content">
        <div class="row">
            <div class="col-lg-4">
                <div class="form-group">
                    <label>Feet:</label>
                    <input type="number"
                        class="form-control @error('feet') is-invalid @enderror"
                        min="5"
                        max="7"
                        name="feet"
                        placeholder="Enter feet"
                        value="{{ old('feet', $wrestler->feet) }}"
                    >
                    @error('feet')
                        <div id="feet-error" class="error invalid-feedback">{{ $messasge }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <label>Inches:</label>
                    <input type="number"
                        class="form-control @error('inches') is-invalid @enderror"
                        max="11" name="inches"
                        placeholder="Enter inches"
                        value="{{ old('inches', $wrestler->inches) }}"
                    >
                    @error('inches')
                        <div id="inches-error" class="error invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <label>Weight:</label>
                    <input type="number"
                        class="form-control @error('weight') is-invalid @enderror"
                        name="weight"
                        placeholder="Enter weight"
                        value="{{ old('weight', $wrestler->weight) }}"
                    >
                    @error('weight')
                        <div id="weight-error" class="error invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>
