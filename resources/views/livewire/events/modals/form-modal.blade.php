<x-form-modal>
    <x-form-modal.modal-input>
        <x-form.inputs.text label="{{ __('events.name') }}" wire:model="modelForm.name" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.date label="{{ __('events.date') }}" wire:model="modelForm.date" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select label="{{ __('events.venue') }}" wire:model="modelForm.venue" :options="$this->getVenues" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.textarea label="{{ __('events.preview') }}" wire:model="modelForm.preview" />
    </x-form-modal.modal-input>
</x-form-modal>
