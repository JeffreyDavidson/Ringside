<x-actions-dropdown>
    @can('view', $venue)
        <x-buttons.view :route="route('venues.show', $venue)" />
    @endcan

    @can('update', $venue)
        <x-buttons.edit :route="route('venues.edit', $venue)" />
    @endcan

    @can('delete', $venue)
        <x-buttons.delete :route="route('venues.destroy', $venue)" />
    @endcan
</x-actions-dropdown>
