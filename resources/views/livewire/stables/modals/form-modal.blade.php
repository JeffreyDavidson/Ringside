<x-modal>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-1">
            <x-form.inputs.text label="{{ __('stables.name') }}" name="modelForm.name" placeholder="Testing Name Here"
                wire:model="modelForm.name" />
        </div>

        <div class="flex flex-col gap-1">
            <x-form.inputs.date label="{{ __('activations.started_at') }}" name="modelForm.start_date"
                wire:model="modelForm.start_date" />
        </div>
    </div>

    <x-slot:footer>
        <x-form.footer />
    </x-slot:footer>
</x-modal>
