<x-actions-dropdown>
    @can('view', $event)
        <x-buttons.view :route="route('events.show', $event)" />
    @endcan

    @can('update', $event)
        <x-buttons.edit :route="route('events.edit', $event)" />
    @endcan

    @can('delete', $event)
        <x-buttons.delete :route="route('events.destroy', $event)" />
    @endcan
</x-actions-dropdown>
