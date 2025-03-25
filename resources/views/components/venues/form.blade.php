<div class="mb-10">
    <x-form.inputs.text label="Name:" name="name" placeholder="Venue Name Here" :value="old('name', $venue->name)"/>
</div>

<div class="mb-10">
    <x-form.inputs.text label="Street Address:" name="street_address" placeholder="Street Address Here" :value="old('street_address', $venue->street_address)"/>
</div>

<div class="mb-10">
    <div class="mb-5 row gx-10">
        <div class="col-lg-4">
            <x-form.inputs.text label="City:" name="city" placeholder="Orlando" :value="old('city', $venue->city)"/>
        </div>

        <div class="col-lg-4">
            <x-form.inputs.select label="State:" name="state" :options="$states" :selected="old('state', $venue->state)" />
        </div>

        <div class="col-lg-4">
            <x-form.inputs.text label="Zip Code:" name="zipcode" placeholder="12345" :value="old('zipcode', $venue->zipcode)"/>
        </div>
    </div>
</div>
