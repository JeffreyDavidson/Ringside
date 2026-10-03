@props(['firstNameLabel', 'lastNameLabel'])

<x-layouts.form-grid :columns="2">
    <x-form-modal.modal-input>
        <x-form.inputs.text :label="$firstNameLabel" wire:model="form.first_name" required autofocus />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.text :label="$lastNameLabel" wire:model="form.last_name" required />
    </x-form-modal.modal-input>
</x-layouts.form-grid>
