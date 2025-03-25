<x-form-modal>
    <x-form-modal.modal-input>
        <x-form.inputs.select label="{{ __('event-matches.match_type') }}" wire:model="modelForm.matchTypeId"
            :options="$this->getMatchTypes" selected="modelForm.matchTypeId" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select label="{{ __('event-matches.referees') }}" wire:model="modelForm.referees" :options="$this->getReferees"
            selected="modelForm.referees" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select label="{{ __('event-matches.titles') }}" wire:model="modelForm.titles" :options="$this->getTitles"
            selected="modelForm.titles" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.textarea label="{{ __('event-matches.preview') }}" wire:model="modelForm.preview" />
    </x-form-modal.modal-input>
</x-form-modal>
