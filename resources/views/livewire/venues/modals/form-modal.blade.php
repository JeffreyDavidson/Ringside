<x-form-modal>
    <x-form-modal.modal-input>
        <x-form.inputs.text label="{{ __('venues.name') }}" wire:model="modelForm.name" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.text label="{{ __('venues.street_address') }}" wire:model="modelForm.street_address" />
    </x-form-modal.modal-input>

    <x-layouts.form-grid :columns="3">
        <x-form-modal.modal-input>
            <x-form.inputs.text label="{{ __('venues.city') }}" wire:model="modelForm.city" />
        </x-form-modal.modal-input>

        <x-form-modal.modal-input>
            <x-form.inputs.text label="{{ __('venues.state') }}" wire:model="modelForm.state" />
        </x-form-modal.modal-input>

        <x-form-modal.modal-input>
            <x-form.inputs.text label="{{ __('venues.zipcode') }}" wire:model="modelForm.zipcode" />
        </x-form-modal.modal-input>
    </x-layouts.form-grid>
</x-form-modal>
