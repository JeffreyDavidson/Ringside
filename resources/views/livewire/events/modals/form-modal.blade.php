<x-form-modal>
    <x-form-modal.modal-input>
        <x-form.inputs.text label="{{ __('events.name') }}" wire:model="form.name" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.date label="{{ __('events.date') }}" wire:model="form.date" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select label="{{ __('events.venue') }}" wire:model="form.venue_id" :options="$this->getVenues" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.textarea label="{{ __('events.preview') }}" wire:model="form.preview" />
    </x-form-modal.modal-input>
</x-form-modal>
