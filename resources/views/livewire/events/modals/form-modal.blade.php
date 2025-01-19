<x-modal>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-1">
            <x-form.inputs.text label="{{ __('events.name') }}" name="modelForm.name" placeholder="Testing Name Here"
                wire:model="modelForm.name" />
        </div>

        <div class="flex flex-col gap-1">
            <x-form.inputs.date label="{{ __('events.date') }}" name="modelForm.date" wire:model="modelForm.date" />
        </div>

        <div class="flex flex-col gap-1">
            <x-form.inputs.select :options="$this->getVenues" label="{{ __('events.venue') }}" name="modelForm.venue"
                wire:model="modelForm.venue" />
        </div>

        <div class="flex flex-col gap-1">
            <x-form.inputs.textarea label="{{ __('events.preview') }}" name="modelForm.preview"
                placeholder="Example event preview" wire:model="modelForm.preview" />
        </div>
    </div>

    <x-slot:footer>
        <x-form.footer />
    </x-slot:footer>
</x-modal>
