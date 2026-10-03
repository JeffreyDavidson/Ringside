<x-form-modal>
    <x-form-modal.modal-input>
        <x-form.inputs.text :label="__('venues.name')" wire:model="form.name" required initial-focus />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.text :label="__('venues.street_address')" wire:model="form.street_address" required />
    </x-form-modal.modal-input>

    <x-layouts.form-grid :columns="3" data-test="venue-address-grid">
        <x-form-modal.modal-input>
            <x-form.inputs.text :label="__('venues.city')" wire:model="form.city" required />
        </x-form-modal.modal-input>

        <x-form-modal.modal-input>
            <x-form.inputs.text :label="__('venues.state')" wire:model="form.state" required />
        </x-form-modal.modal-input>

        <x-form-modal.modal-input>
            <x-form.inputs.text :label="__('venues.zipcode')" wire:model="form.zipcode" inputmode="numeric" required />
        </x-form-modal.modal-input>
    </x-layouts.form-grid>
</x-form-modal>
