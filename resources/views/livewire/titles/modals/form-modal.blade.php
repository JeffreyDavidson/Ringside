<x-form-modal>
    <x-form-modal.modal-input>
        <x-form.inputs.text label="{{ __('titles.name') }}" wire:model="form.name" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select label="{{ __('titles.type') }}" wire:model="form.type" :options="$this->getTitleTypes" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.date label="{{ __('activations.started_at') }}" wire:model="form.start_date" />
    </x-form-modal.modal-input>
</x-form-modal>
