<x-form-modal>
    <x-form-modal.modal-input>
        <x-form.inputs.text label="{{ __('stables.name') }}" wire:model="form.name" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.date label="{{ __('activations.started_at') }}" wire:model="form.start_date" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select label="{{ __('core.wrestlers') }}" wire:model="form.wrestlers" :options="$this->getWrestlers"
            selected="form.wrestlers" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select label="{{ __('core.tag-teams') }}" wire:model="form.tag_teams" :options="$this->getTagTeams"
            selected="form.tag_teams" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select label="{{ __('core.managers') }}" wire:model="form.managers" :options="$this->getManagers"
            selected="form.managers" />
    </x-form-modal.modal-input>
</x-form-modal>
