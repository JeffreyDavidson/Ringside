<x-form-modal>
    <x-form-modal.modal-input>
        <x-form.inputs.text label="First Name" wire:model="form.first_name" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.text label="Last Name" wire:model="form.last_name" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.email label="Email" wire:model="form.email" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.password label="Password" wire:model="form.password" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.password label="Confirm Password" wire:model="form.password_confirmation" />
    </x-form-modal.modal-input>
</x-form-modal>