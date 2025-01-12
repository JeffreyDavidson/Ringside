<x-modal>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-1">
            <x-form.inputs.text label="{{ __('venues.name') }}" name="modelForm.name" placeholder="Example Arena Name"
                wire:model="modelForm.name" />
        </div>

        <div class="flex flex-col gap-1">
            <x-form.inputs.text label="{{ __('venues.street_address') }}" name="modelForm.street_address"
                placeholder="123 Main Street" wire:model="modelForm.street_address" />
        </div>

        <div class="flex items-center justify-between gap-1">
            <div class="flex flex-col gap-1">
                <x-form.inputs.text label="{{ __('venues.city') }}" name="modelForm.city" placeholder="Orlando"
                    wire:model="modelForm.city" />
            </div>
            <div class="flex flex-col gap-1">
                <x-form.inputs.text label="{{ __('venues.state') }}" name="modelForm.state" placeholder="FL"
                    wire:model="modelForm.state" />
            </div>
            <div class="flex flex-col gap-1">
                <x-form.inputs.text label="{{ __('venues.zipcode') }}" name="modelForm.zipcode" placeholder="12345"
                    wire:model="modelForm.zipcode" />
            </div>
        </div>
    </div>

    <x-slot:footer>
        <x-form.footer />
    </x-slot:footer>
</x-modal>
