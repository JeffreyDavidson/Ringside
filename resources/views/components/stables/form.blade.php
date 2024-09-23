<div class="mb-10">
    <x-form.inputs.text label="Name:" name="name" placeholder="Stable Name Here" :value="old('name', $stable->name)" />
</div>

<div class="mb-10">
    <x-form.inputs.date label="Start Date:" name="start_date" :value="old('start_date', $stable->activatedAt?->format('m-d-Y'))" />
</div>

<div class="mb-10">
    <x-form.inputs.select label="Wrestlers:" name="wrestlers" :options="$wrestlers" :selected="old('wrestlers')" />
</div>

<div class="mb-10">
    <x-form.inputs.select label="Tag Teams:" name="tag_teams" :options="$tagTeams" :selected="old('tag_teams')" />
</div>

<div class="mb-10">
    <x-form.inputs.select label="Managers:" name="managers" :options="$managers" :selected="old('managers')" />
</div>
