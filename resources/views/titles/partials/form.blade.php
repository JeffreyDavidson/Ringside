<div class="mb-10">
    <x-form.inputs.text
        name="name"
        label="Name"
        :value="old('name', $title->name)"
    />
</div>

<div class="mb-10">
    <x-form.inputs.date
        name="activated_at"
        label="Activation Date"
        :value="old('activated_at', $title->activated_at)"
    />
</div>
