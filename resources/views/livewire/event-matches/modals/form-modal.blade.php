<x-form-modal>
    <x-form-modal.input>
        <x-form.inputs.select label="{{ __('event-matches.match_type') }}" wire:model="matchTypeId" :options="$matchTypes"
            :selected="old('match_type_id')" />
    </x-form-modal.input>

    <x-form-modal.input>
        <x-form.inputs.select label="{{ __('event-matches.referees') }}" id="referees" name="referees" :options="$referees"
            :selected="old('referees')" />
    </x-form-modal.input>

    <x-form-modal.input>
        <x-form.inputs.select label="{{ __('event-matches.titles') }}" :options="$titles" :selected="old('titles')" />
    </x-form-modal.input>

    <x-form-modal.input>
        @include($subViewToUse)
    </x-form-modal.input>

    <x-form-modal.input>
        <x-form.inputs.textarea label="{{ __('event-matches.preview') }}" :value="old('preview')" />
    </x-form-modal.input>
</x-form-modal>
