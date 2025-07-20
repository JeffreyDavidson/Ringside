<x-form-modal>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-form-modal.modal-input>
            <x-form.inputs.text label="{{ __('referees.first_name') }}" wire:model="form.first_name" />
        </x-form-modal.modal-input>

        <x-form-modal.modal-input>
            <x-form.inputs.text label="{{ __('referees.last_name') }}" wire:model="form.last_name" />
        </x-form-modal.modal-input>
    </div>

    <x-form-modal.modal-input>
        <x-form.inputs.date label="{{ __('employments.started_at') }}" wire:model="form.employment_date" />
    </x-form-modal.modal-input>
</x-form-modal>
