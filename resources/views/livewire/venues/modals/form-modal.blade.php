<x-form-modal>
    <x-form-modal.modal-input>
        <x-form.inputs.text label="{{ __('venues.name') }}" wire:model="modelForm.name" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.text label="{{ __('venues.street_address') }}" wire:model="modelForm.street_address" />
    </x-form-modal.modal-input>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-form-modal.modal-input>
            <x-form.inputs.text label="{{ __('venues.city') }}" wire:model="modelForm.city" />
        </x-form-modal.modal-input>

        <x-form-modal.modal-input>
            <x-form.inputs.text label="{{ __('venues.state') }}" wire:model="modelForm.state" />
        </x-form-modal.modal-input>

        <x-form-modal.modal-input>
            <x-form.inputs.text label="{{ __('venues.zipcode') }}" wire:model="modelForm.zipcode" />
        </x-form-modal.modal-input>
    </div>
</x-form-modal>
