<div class="mb-10">
    <x-form.inputs.text label="Name:" name="name" placeholder="Title Name Here" :value="old('name', $title->name)"/>
</div>

<div class="mb-10">
    <x-form.inputs.date label="Activation Date:" name="activation_date" :value="old('activation_date', $title->activated_at?->format('Y-m-d'))"/>
</div>
