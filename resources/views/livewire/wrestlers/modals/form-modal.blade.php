<x-modal>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-1">
            <x-form.inputs.text label="{{ __('wrestlers.name') }}" name="modelForm.name" placeholder="Testing Name Here"
                wire:model="modelForm.name" />
        </div>

        <div class="flex flex-col gap-1">
            <x-form.inputs.text label="{{ __('wrestlers.hometown') }}" name="modelForm.hometown" placeholder="Orlando, FL"
                wire:model="modelForm.hometown" />
        </div>

        <div class="flex items-center justify-between gap-1">
            <div class="flex flex-col gap-1">
                <x-form.inputs.text label="{{ __('wrestlers.feet') }}" name="modelForm.height_feet" placeholder="Feet"
                    wire:model="modelForm.height_feet" />
            </div>
            <div class="flex flex-col gap-1">
                <x-form.inputs.text label="{{ __('wrestlers.inches') }}" name="modelForm.height_inches"
                    placeholder="Inches" wire:model="modelForm.height_inches" />
            </div>
            <div class="flex flex-col gap-1">
                <x-form.inputs.text label="{{ __('wrestlers.weight') }}" name="modelForm.weight" placeholder="lbs"
                    wire:model="modelForm.weight" />
            </div>
        </div>

        <div class="flex flex-col gap-1">
            <x-form.inputs.text label="{{ __('wrestlers.signature_move') }}" name="modelForm.signature_move"
                placeholder="This Amazing Finisher" wire:model="modelForm.signature_move" />
        </div>

        <div class="flex flex-col gap-1">
            <x-form.inputs.date label="{{ __('employments.started_at') }}" name="modelForm.start_date"
                wire:model="modelForm.start_date" />
        </div>
    </div>

    <x-slot:footer>
        <div class="flex gap-4">
            <x-buttons.light wire:click="clear">Clear</x-buttons.light>
            <x-buttons.primary wire:click="save">Save</x-buttons.primary>
        </div>
    </x-slot:footer>
</x-modal>
