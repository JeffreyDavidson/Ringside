<div>
    <!-- It is not the man who has too little, but the man who craves more, that is poor. - Seneca -->
</div>
@props(['firstNameLabel', 'lastNameLabel'])

<x-layouts.form-grid :columns="2">
    <x-form-modal.modal-input>
        <x-form.inputs.text :label="$firstNameLabel" wire:model="form.first_name" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.text :label="$lastNameLabel" wire:model="form.last_name" />
    </x-form-modal.modal-input>
</x-layouts.form-grid>
