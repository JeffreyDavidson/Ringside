<div>
    <x-form-modal>
        <x-form-modal.person-name-fields first-name-label="First Name" last-name-label="Last Name" />

        <x-form-modal.modal-input>
            <x-form.inputs.text label="Email" wire:model="form.email" />
        </x-form-modal.modal-input>

        <x-form-modal.modal-input>
            <x-form.inputs.select
                label="Role"
                wire:model="form.role"
                :options="['basic' => 'Basic', 'administrator' => 'Administrator']"
                :selected="$form->role ?? 'basic'"
            />
        </x-form-modal.modal-input>

        <x-form-modal.modal-input>
            <x-form.inputs.text label="Password" wire:model="form.password" />
        </x-form-modal.modal-input>

        <x-form-modal.modal-input>
            <x-form.inputs.text label="Confirm Password" wire:model="form.password_confirmation" />
        </x-form-modal.modal-input>
    </x-form-modal>
</div>
