<div class="flex justify-between flex-1">
    <div>
        @env('local')
        @empty($this->modelForm->formModel)
            <x-buttons.info wire:click="fillDummyFields">Auto Fill</x-buttons.info>
        @endempty
        @endenv
    </div>
    <div>
        <x-buttons.light wire:click="clear">Clear</x-buttons.light>
        <x-buttons.primary wire:click="save">Save</x-buttons.primary>
    </div>
</div>
