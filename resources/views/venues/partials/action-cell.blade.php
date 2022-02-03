<x-actions-dropdown>
    @can('update', $venue)
        <div class="px-3 menu-item">
            <x-buttons.edit :route="route('venues.edit', $venue)" />
        </div>
    @endcan

    @can('delete', $venue)
        <div class="px-3 menu-item">
            <x-buttons.delete wire:click="delete($venue)" />
        </div>
    @endcan
</x-actions-dropdown>
