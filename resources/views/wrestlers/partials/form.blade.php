<x-kt-section title="General Information">
    <div class="form-group">
        <x-form.inputs.text
            name="name"
            label="Name"
            :value="old('name', $wrestler->name)"
        />
    </div>
    <div class="form-group row">
        <div class="col-lg-4">
            <x-form.inputs.text
                name="hometown"
                label="Hometown"
                :value="old('hometown', $wrestler->hometown)"
            />
        </div>
        <div class="col-lg-4">
            <x-form.inputs.date
                name="started_at"
                label="Started At"
                :value="old('started_at', $wrestler->startedAt)"
            />
        </div>
        <div class="col-lg-4">
            <x-form.inputs.text
                name="signature_move"
                label="Signature Move"
                :value="old('signature_move', $wrestler->signature_move)"
            />
        </div>
    </div>
</x-kt-section>
<x-kt-section title="Physical Information">
    <div class="form-group row">
        <div class="col-lg-4">
            <x-form.inputs.number
                name="feet"
                min="5"
                max="7"
                label="Feet"
                :value="old('feet', $wrestler->feet)" />
        </div>
        <div class="col-lg-4">
            <x-form.inputs.number
                name="inches"
                max="11"
                label="Inches"
                :value="old('inches', $wrestler->inches)" />
        </div>
        <div class="col-lg-4">
            <x-form.inputs.number
                name="weight"
                label="Weight"
                label="Weight"
                :value="old('weight', $wrestler->weight)" />
        </div>
    </div>
</x-kt-section>
