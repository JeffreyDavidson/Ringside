<div class="mb-10">
    <x-form.inputs.text label="Name:" name="name" placeholder="Wrestler Name Here" :value="old('name', $wrestler->name)"/>
</div>

<div class="mb-10">
    <div class="mb-5 row gx-10">
        <div class="col-lg-3">
            <x-form.inputs.number label="Height (Feet):" name="feet" max="8" :value="old('feet', $wrestler->height->feet)"/>
        </div>

        <div class="col-lg-3">
            <x-form.inputs.number label="Height (Inches):" name="inches" max="11" :value="old('inches', $wrestler->height->inches)"/>
        </div>

        <div class="col-lg-6">
            <x-form.inputs.number label="Weight:" name="weight" :value="old('weight', $wrestler->weight)"/>
        </div>
    </div>
</div>

<div class="mb-10">
    <x-form.inputs.text label="Hometown:" name="hometown" placeholder="Orlando, FL" :value="old('hometown', $wrestler->hometown)"/>
</div>

<div class="mb-10">
    <x-form.inputs.text label="Signature Move:" name="signature_move" placeholder="This Amazing Finisher" :value="old('signature_move', $wrestler->signature_move)"/>
</div>

<div class="mb-10">
    <x-form.inputs.date label="Start Date:" name="start_date" :value="old('start_date', $wrestler->started_at?->format('Y-m-d'))"/>
</div>
