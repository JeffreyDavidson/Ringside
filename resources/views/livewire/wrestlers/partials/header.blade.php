<x-table.header>
    <x-card.title>
        <x-search resource="Wrestlers"/>
    </x-card.title>

    <x-card.toolbar>
        <x-card.toolbar.actions x-show.important="$wire.selectedWrestlerIds.length == 0">
            <x-table.filters>
                <x-wrestler.index.filters/>
            </x-table.filters>
            <x-buttons.create
                route="{{ route('wrestlers.create') }}"
                resource="Wrestler"
                x-cloak
            />
        </x-card.toolbar.actions>
        <x-wrestler.index.delete-selected
            x-show="$wire.selectedWrestlerIds.length > 0"
        />
    </x-card.toolbar>
</x-table.header>
