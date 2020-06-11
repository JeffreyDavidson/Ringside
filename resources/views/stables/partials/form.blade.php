<x-kt-section title="General Information">
    <div class="form-group">
        <x-form.inputs.text
            name="name"
            label="Name"
            :value="old('name', $stable->name)"
        />
    </div>
    <div class="form-group">
        <x-form.inputs.date
            name="started_at"
            label="Started At"
            :value="old('started_at', $stable->startedAt)"
        />
    </div>
</x-kt-section>
