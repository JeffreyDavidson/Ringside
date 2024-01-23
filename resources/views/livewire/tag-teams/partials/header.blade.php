<x-table.header>
    <x-card.title>
        <x-search resource="Tag Teams" />
    </x-card.title>

    <x-card.toolbar>
        <card.toolbar.actions>
            <x-buttons.create route="{{ route('tag-teams.create') }}" resource="Tag Team" />
        </card.toolbar.actions>
    </x-card.toolbar>
</x-table.header>
