<div class="mb-10">
    <x-form.inputs.text
        name="name"
        label="Name"
        :value="old('name', $event->name)"
    />
</div>

<div class="mb-10">
    <x-form.inputs.date
        name="date"
        label="Date"
        :value="old('date', $event->date)"
    />
</div>

<div class="mb-10">
    <x-form.inputs.select
        name="venue"
        label="Venue"
        :selected="old('venue', $event->venue_id)"
        :options="\App\Models\Venue::withTrashed()->pluck('name', 'id')"
    />
</div>

<div class="mb-10">
    <x-form.inputs.textarea
        name="preview"
        label="Preview"
        :value="old('preview', $event->preview)"
    />
</div>
