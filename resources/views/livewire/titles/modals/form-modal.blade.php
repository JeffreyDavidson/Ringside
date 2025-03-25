<x-form-modal>
    <x-form-modal.modal-input>
        <x-form.inputs.text label="{{ __('titles.name') }}" wire:model="modelForm.name" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.date label="{{ __('activations.started_at') }}" wire:model="modelForm.start_date" />
    </x-form-modal.modal-input>
</x-form-modal>
