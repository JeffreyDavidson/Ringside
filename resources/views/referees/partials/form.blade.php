<div class="form-group row">
    <div class="col-lg-6">
        <x-form.inputs.text
            name="first_name"
            label="First Name"
            :value="old('first_name', $referee->first_name)"
        />
    </div>

    <div class="col-lg-6">
        <x-form.inputs.text
            name="last_name"
            label="Last Name"
            :value="old('last_name', $referee->last_name)"
        />
    </div>
</div>

<div class="form-group">
    <x-form.inputs.date
        name="started_at"
        label="Started At"
        :value="old('started_at', $referee->started_at)"
    />
</div>
