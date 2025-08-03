<x-form-modal>
    <x-layouts.form-grid :columns="2">
        <x-form-modal.modal-input>
            <x-form.inputs.text label="{{ __('managers.first_name') }}" wire:model="modelForm.first_name" />
        </x-form-modal.modal-input>

        <x-form-modal.modal-input>
            <x-form.inputs.text label="{{ __('managers.last_name') }}" wire:model="modelForm.last_name" />
        </x-form-modal.modal-input>
    </x-layouts.form-grid>

    <x-form-modal.modal-input>
        <x-form.inputs.date label="{{ __('employments.started_at') }}" wire:model="modelForm.start_date" />
    </x-form-modal.modal-input>
</x-form-modal>
