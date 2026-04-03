<div class="mb-10">
    <div class="mb-5 grid grid-cols-1 lg:grid-cols-2 gap-5">
        <x-form.inputs.text label="First Name:" name="first_name" placeholder="First Name Here" :value="old('first_name', $manager->first_name)"/>
        <x-form.inputs.text label="Last Name:" name="last_name" placeholder="Last Name Here" :value="old('last_name', $manager->last_name)"/>
    </div>
</div>

<div class="mb-10">
    <x-form.inputs.date label="Start Date:" name="start_date" :value="old('start_date', $manager->started_at?->format('Y-m-d'))"/>
</div>
