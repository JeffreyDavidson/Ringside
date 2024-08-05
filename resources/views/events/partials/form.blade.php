<div class="mb-10">
    <x-form.inputs.text label="Name:" name="name" placeholder="Event Name Here" :value="old('name')"/>
</div>

<div class="mb-10">
    <x-form.inputs.date label="Date:" name="date" :value="old('date')"/>
</div>

<div class="mb-10">
    <x-form.inputs.select label="Venue:" name="venue_id" :options="$venues" :selected="old('venue_id')"/>
</div>

<div class="mb-10">
    <x-form.inputs.textarea label="Preview:" name="preview" placeholder="Enter a preview description of the event." :value="old('preview')"/>
</div>
