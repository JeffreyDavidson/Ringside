<x-form-modal>
    <div class="flex flex-col">
        <x-form.inputs.text label="{{ __('tag-teams.name') }}" name="modelForm.name" placeholder="Testing Name Here"
            wire:model="modelForm.name" />
    </div>

    <div class="flex flex-col">
        <x-form.inputs.text label="{{ __('tag-teams.signature_move') }}" name="modelForm.signature_move"
            placeholder="This Amazing Finisher" wire:model="modelForm.signature_move" />
    </div>

    <div class="flex flex-col">
        <x-form.inputs.date label="{{ __('employments.started_at') }}" name="modelForm.start_date"
            wire:model="modelForm.start_date" />
    </div>
</x-form-modal>
