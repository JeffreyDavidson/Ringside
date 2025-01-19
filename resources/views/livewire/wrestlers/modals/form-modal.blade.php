<x-modal>
    <div class="flex flex-col space-y-4">
        <div class="flex flex-col">
            <x-form.inputs.text label="{{ __('wrestlers.name') }}" name="modelForm.name" placeholder="Testing Name Here"
                wire:model="modelForm.name" />
        </div>

        <div class="flex flex-col">
            <x-form.inputs.text label="{{ __('wrestlers.hometown') }}" name="modelForm.hometown" placeholder="Orlando, FL"
                wire:model="modelForm.hometown" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex flex-col">
                <x-form.inputs.text label="{{ __('wrestlers.feet') }}" name="modelForm.height_feet" placeholder="Feet"
                    wire:model="modelForm.height_feet" />
            </div>
            <div class="flex flex-col">
                <x-form.inputs.text label="{{ __('wrestlers.inches') }}" name="modelForm.height_inches"
                    placeholder="Inches" wire:model="modelForm.height_inches" />
            </div>
            <div class="flex flex-col">
                <x-form.inputs.text label="{{ __('wrestlers.weight') }}" name="modelForm.weight" placeholder="lbs"
                    wire:model="modelForm.weight" />
            </div>
        </div>

        <div class="flex flex-col">
            <x-form.inputs.text label="{{ __('wrestlers.signature_move') }}" name="modelForm.signature_move"
                placeholder="This Amazing Finisher" wire:model="modelForm.signature_move" />
        </div>

        <div class="flex flex-col">
            <x-form.inputs.date label="{{ __('employments.started_at') }}" name="modelForm.start_date"
                wire:model="modelForm.start_date" />
        </div>
    </div>

    <x-slot:footer>
        <x-form.footer />
    </x-slot:footer>
</x-modal>
