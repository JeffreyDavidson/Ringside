<x-form-modal>
    <x-form-modal.modal-input>
        <x-form.inputs.text label="{{ __('tag-teams.name') }}" wire:model="form.name" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.text label="{{ __('tag-teams.signature_move') }}" wire:model="form.signature_move" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.date label="{{ __('employments.started_at') }}" wire:model="form.employment_date" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select label="{{ __('tag-teams.wrestlerA') }}" wire:model="form.wrestlerA" :options="$this->getWrestlers"
            selected="form.wrestlerA" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select label="{{ __('tag-teams.wrestlerB') }}" wire:model="form.wrestlerB" :options="$this->getWrestlers"
            selected="form.wrestlerB" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select label="{{ __('tag-teams.managers') }}" wire:model="form.managers" :options="$this->getManagers" :multiple="true" />
    </x-form-modal.modal-input>
</x-form-modal>
