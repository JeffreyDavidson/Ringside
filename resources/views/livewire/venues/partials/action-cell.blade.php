<x-actions-dropdown>
    @can('update', $venue)
        <x-buttons.edit :route="route('venues.edit', $venue)" />
    @endcan

    @can('delete', $venue)
        <x-buttons.delete wire:click="delete($venue)" />
    @endcan
</x-actions-dropdown>
