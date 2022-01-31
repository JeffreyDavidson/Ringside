<div class="form-group">
    <x-form.inputs.text
        name="name"
        label="Venue Name"
        :value="old('name', $venue->name)"
    />
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="form-group">
            <x-form.inputs.text
                name="address1"
                label="Street Address"
                :value="old('address1', $venue->address1)"
            />
        </div>
    </div>
    <div class="col-lg-4">
        <div class="form-group">
            <x-form.inputs.text
                name="address2"
                label="Suite Number"
                :value="old('address2', $venue->address2)"
            />
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-4">
        <div class="form-group">
            <x-form.inputs.text
                name="city"
                label="City"
                :value="old('city', $venue->city)"
            />
        </div>
    </div>
    <div class="col-lg-4">
        <div class="form-group">
            <x-form.inputs.text
                name="state"
                label="State"
                :value="old('state', $venue->state)"
            />
        </div>
    </div>
    <div class="col-lg-4">
        <div class="form-group">
            <x-form.inputs.text
                name="zip"
                label="Zip"
                :value="old('zip', $venue->zip)"
            />
        </div>
    </div>
</div>
