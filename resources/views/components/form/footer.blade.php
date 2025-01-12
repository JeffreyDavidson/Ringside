<div class="flex gap-4">
    @env('local')
    <x-buttons.info wire:click="fillDummyFields">Auto Fill</x-buttons.info>
    @endenv
    <x-buttons.light wire:click="clear">Clear</x-buttons.light>
    <x-buttons.primary wire:click="save">Save</x-buttons.primary>
</div>
