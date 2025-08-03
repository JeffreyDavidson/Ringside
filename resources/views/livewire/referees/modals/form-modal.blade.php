<x-form-modal>
    <x-layouts.form-grid :columns="2">
        <x-form-modal.modal-input>
            <x-form.inputs.text label="{{ __('referees.first_name') }}" wire:model="form.first_name" />
        </x-form-modal.modal-input>

        <x-form-modal.modal-input>
            <x-form.inputs.text label="{{ __('referees.last_name') }}" wire:model="form.last_name" />
        </x-form-modal.modal-input>
    </x-layouts.form-grid>

    <x-form-modal.modal-input>
        <x-form.inputs.date label="{{ __('employments.started_at') }}" wire:model="form.employment_date" />
    </x-form-modal.modal-input>
</x-form-modal>
