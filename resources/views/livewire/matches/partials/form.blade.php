<x-form :action="route('events.matches.store', $event)">
    <div class="mb-10">
        <x-form.inputs.select
            label="Match Type:"
            id="match_type_id"
            name="match_type_id"
            :options="$matchTypes"
            :selected="old('match_type_id', $match->match_type_id)"
            wire:model="matchTypeId"
        />
    </div>
    <div class="mb-10">
        <x-form.inputs.select
            label="Referees:"
            id="referees"
            name="referees"
            :options="$referees"
            :selected="old('referees', $match->referees?->modelKeys())"
        />
    </div>
    <div class="mb-10">
        <x-form.inputs.select
            label="Titles:"
            id="titles"
            name="titles"
            :options="$titles"
            :selected="old('titles', $match->titles?->modelKeys())"
        />
    </div>
    @if ($subViewToUse)
        <div class="mb-10">
            @include($subViewToUse)
        </div>
    @endif
    <div class="mb-10">
        <x-form.inputs.textarea
            name="preview"
            label="Preview"
            :value="old('preview', $match->preview)"
        />
    </div>

    <x-form.footer />
</x-form>
