<x-form-modal>
    <x-form-modal.modal-input>
        <x-form.inputs.text label="{{ __('wrestlers.name') }}" wire:model="modelForm.name" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.text label="{{ __('wrestlers.hometown') }}" wire:model="modelForm.hometown" />
    </x-form-modal.modal-input>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-form-modal.modal-input>
            <x-form.inputs.text label="{{ __('wrestlers.feet') }}" wire:model="modelForm.height_feet" />
        </x-form-modal.modal-input>

        <x-form-modal.modal-input>
            <x-form.inputs.text label="{{ __('wrestlers.inches') }}" wire:model="modelForm.height_inches" />
        </x-form-modal.modal-input>

        <x-form-modal.modal-input>
            <x-form.inputs.text label="{{ __('wrestlers.weight') }}" wire:model="modelForm.weight" />
        </x-form-modal.modal-input>
    </div>

    <x-form-modal.modal-input>
        <x-form.inputs.text label="{{ __('wrestlers.signature_move') }}" wire:model="modelForm.signature_move" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.date label="{{ __('employments.started_at') }}" wire:model="modelForm.start_date" />
    </x-form-modal.modal-input>
</x-form-modal>
