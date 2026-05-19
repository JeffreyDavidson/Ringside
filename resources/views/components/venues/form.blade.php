<div class="mb-10">
    <x-form.inputs.text label="Name:" name="name" placeholder="Venue Name Here" :value="old('name', $venue->name)"/>
</div>

<div class="mb-10">
    <x-form.inputs.text label="Street Address:" name="street_address" placeholder="Street Address Here" :value="old('street_address', $venue->street_address)"/>
</div>

<div class="mb-10">
    <div class="mb-5 grid grid-cols-1 lg:grid-cols-3 gap-5">
        <x-form.inputs.text label="City:" name="city" placeholder="Orlando" :value="old('city', $venue->city)"/>
        <x-form.inputs.select label="State:" name="state" :options="$states" :selected="old('state', $venue->state)" />
        <x-form.inputs.text label="Zip Code:" name="zipcode" placeholder="12345" :value="old('zipcode', $venue->zipcode)"/>
    </div>
</div>
