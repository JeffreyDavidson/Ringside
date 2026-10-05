<x-form-modal>
    <x-form-modal.person-name-fields
        :first-name-label="__('managers.first_name')"
        :last-name-label="__('managers.last_name')"
    />

    @unless ($this->form->hasEmploymentHistory)
        <x-form-modal.modal-input>
            <x-form.inputs.date :label="__('employments.started_at')" wire:model="form.employment_date" />
        </x-form-modal.modal-input>
    @endunless
</x-form-modal>
