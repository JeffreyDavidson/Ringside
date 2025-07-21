<x-form-modal>
    <x-form-modal.modal-input>
        <x-form.inputs.select label="Match Type" wire:model="form.matchTypeId" :options="$this->getMatchTypes" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select label="Wrestlers" wire:model="form.competitors.0.wrestlers" :options="$this->getWrestlers" multiple />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select label="Tag Teams" wire:model="form.competitors.1.tag_teams" :options="$this->getTagTeams" multiple />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select label="Referees" wire:model="form.referees" :options="$this->getReferees" multiple />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select label="Titles" wire:model="form.titles" :options="$this->getTitles" multiple />
    </x-form-modal.modal-input>
    <x-form-modal.modal-input>
        <x-form.inputs.textarea label="Preview" wire:model="form.preview" />
    </x-form-modal.modal-input>
</x-form-modal>
