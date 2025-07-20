<x-form-modal>
    <x-form-modal.modal-input>
        <x-form.inputs.text label="Name" wire:model="form.name" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.text label="Email" wire:model="form.email" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.text label="Password" wire:model="form.password" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.text label="Confirm Password" wire:model="form.password_confirmation" />
    </x-form-modal.modal-input>
</x-form-modal>