<div class="mb-10">
    <x-form.inputs.text
        label="Name:"
        name="name"
        placeholder="Tag Team Name Here"
        :value="old('name', $tagTeam->name)"
    />
</div>
<div class="mb-10">
    <x-form.inputs.select
        label="Tag Team Partner 1:"
        name="wrestlerA"
        :options="$wrestlers"
        :selected="old('wrestlerA', $tagTeam->currentWrestlers->first()->id ?? 0)"
    />
</div>
<div class="mb-10">
    <x-form.inputs.select
        label="Tag Team Partner 2:"
        name="wrestlerB"
        :options="$wrestlers"
        :selected="old('wrestlerB', $tagTeam->currentWrestlers->last()->id ?? 0)"
    />
</div>
<div class="mb-10">
    <x-form.inputs.text
        label="Signature Move:"
        name="signature_move"
        placeholder="This Amazing Finisher"
        :value="old('signature_move', $tagTeam->signature_move)"
    />
</div>
<div class="mb-10">
    <x-form.inputs.date
        label="Start Date:"
        name="start_date"
        :value="old('start_date', $tagTeam->started_at?->format('Y-m-d'))"
    />
</div>

<x-form.footer />
