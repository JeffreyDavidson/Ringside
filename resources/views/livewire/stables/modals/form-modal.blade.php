<x-form-modal>
    <x-form-modal.modal-input>
        <x-form.inputs.text label="{{ __('stables.name') }}" wire:model="modelForm.name" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.date label="{{ __('activations.started_at') }}" wire:model="modelForm.start_date" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select label="{{ __('core.wrestlers') }}" wire:model="modelForm.wrestlers" :options="$this->getWrestlers"
            selected="modelForm.wrestlers" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select label="{{ __('core.tag-teams') }}" wire:model="modelForm.tag_teams" :options="$this->getTagTeams"
            selected="modelForm.tagTeams" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select label="{{ __('core.managers') }}" wire:model="modelForm.managers" :options="$this->getManagers"
            selected="modelForm.managers" />
    </x-form-modal.modal-input>
</x-form-modal>
