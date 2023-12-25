<div class="mb-10">
    <div class="mb-5 row gx-10">
        <div class="col-lg-6">
            <x-form.inputs.text
                label="First Name:"
                name="first_name"
                placeholder="First Name Here"
                :value="old('first_name', $manager->first_name)"
            />
        </div>
        <div class="col-lg-6">
            <x-form.inputs.text
                label="Last Name:"
                name="last_name"
                placeholder="Last Name Here"
                :value="old('last_name', $manager->last_name)"
            />
        </div>
    </div>
</div>
<div class="mb-10">
    <x-form.inputs.date
        label="Start Date:"
        name="start_date"
        :value="old('start_date', $manager->started_at?->format('Y-m-d'))"
    />
</div>

<x-form.footer />
