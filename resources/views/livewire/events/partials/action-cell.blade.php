<x-actions-dropdown>
    @can('update', $event)
        <x-buttons.edit :route="route('events.edit', $event)" />
    @endcan

    @can('delete', $event)
        <x-buttons.delete :route="route('events.destroy', $event)" />
    @endcan

    @can('addMatches', $event)
        <x-menu.menu-link>
            <a href="{{ route('events.matches.create', $event) }}" class="px-3 menu-link">Add Match</a>
        </x-menu.menu-link>
    @endcan
</x-actions-dropdown>
